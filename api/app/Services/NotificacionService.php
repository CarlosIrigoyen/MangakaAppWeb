<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\ClienteMangaSuscripcion;
use App\Models\ClienteDispositivo;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Exception\MessagingException;

class NotificacionService
{
    protected $messaging = null;
    protected $credentialsPath = null;

    public function __construct()
    {
        Log::info("🔄 Inicializando NotificacionService...");

        // USAR SOLO FIREBASE_CREDENTIALS_BASE64
        $base64 = env('FIREBASE_CREDENTIALS_BASE64');

        if (empty($base64)) {
            Log::error("❌ FIREBASE_CREDENTIALS_BASE64 no está configurada en el entorno");
            return;
        }

        try {
            // Decodificar base64 a JSON
            $json = base64_decode($base64, true);
            if ($json === false) {
                throw new \RuntimeException("Base64 inválido en FIREBASE_CREDENTIALS_BASE64");
            }

            // Validar JSON decodificado
            $decoded = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException("Contenido de credenciales no es JSON válido: " . json_last_error_msg());
            }

            // Escribir archivo temporal en storage (no versionado)
            $tempPath = storage_path('app/firebase_credentials.json');
            if (!is_dir(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0700, true);
            }

            file_put_contents($tempPath, $json, LOCK_EX);
            @chmod($tempPath, 0600);

            $this->credentialsPath = $tempPath;
            Log::info("📄 Archivo temporal Firebase creado en: " . $tempPath);

            // Inicializar Firebase Admin SDK
            $factory = (new Factory)->withServiceAccount($tempPath);
            $this->messaging = $factory->createMessaging();

            Log::info("✅ SDK Firebase inicializado correctamente");
        } catch (\Throwable $e) {
            Log::error("❌ Error inicializando Firebase: " . $e->getMessage());
            $this->messaging = null;
        }
    }

    /**
     * Notificar nuevo tomo a suscriptores
     */
    public function notificarNuevoTomo(int $mangaId, $numeroTomo): bool
    {
        Log::info("🔔 NOTIFICAR NUEVO TOMO - Manga: {$mangaId}, Tomo: {$numeroTomo}");

        if (!$this->messaging) {
            Log::error("❌ Firebase Messaging no está inicializado");
            return false;
        }

        // 1) Obtener todos los clientes suscritos al manga
        $clienteIds = ClienteMangaSuscripcion::where('manga_id', $mangaId)
            ->pluck('cliente_id')
            ->unique()
            ->values()
            ->toArray();

        Log::info("📋 Clientes suscritos: " . count($clienteIds));

        if (empty($clienteIds)) {
            Log::warning("⚠️ No hay clientes suscritos para el manga {$mangaId}");
            return false;
        }

        // 2) Obtener todos los tokens FCM de los dispositivos asociados a esos clientes
        $tokens = ClienteDispositivo::whereIn('cliente_id', $clienteIds)
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->pluck('fcm_token')
            ->unique()
            ->values()
            ->toArray();

        Log::info("📋 Tokens encontrados: " . count($tokens));

        if (empty($tokens)) {
            Log::warning("⚠️ No hay tokens para el manga {$mangaId}");
            return false;
        }

        $mangaTitulo = \App\Models\Manga::find($mangaId)->titulo ?? "Manga #{$mangaId}";

        $title = "🎉 Nuevo tomo disponible!";
        $body = "{$mangaTitulo} - Tomo #{$numeroTomo} ya está disponible";

        $data = [
            'manga_id' => (string)$mangaId,
            'numero_tomo' => (string)$numeroTomo,
            'type' => 'nuevo_tomo',
            'url' => '/mangas/' . $mangaId,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK'
        ];

        return $this->enviarNotificacion($tokens, $title, $body, $data);
    }

    /**
     * Enviar notificación usando Firebase Admin SDK
     */
    private function enviarNotificacion(array $tokens, string $title, string $body, array $data): bool
    {
        try {
            Log::info("🚀 Enviando notificación a " . count($tokens) . " tokens");

            $notification = Notification::create($title, $body);

            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withData($data)
                ->withHighestPossiblePriority();

            $successCount = 0;
            $failureCount = 0;

            // Enviar en chunks de 500 tokens
            foreach (array_chunk($tokens, 500) as $chunkIndex => $chunk) {
                Log::info("📦 Procesando chunk {$chunkIndex} con " . count($chunk) . " tokens");

                try {
                    $report = $this->messaging->sendMulticast($message, $chunk);

                    $chunkSuccess = $report->successes()->count();
                    $chunkFailure = $report->failures()->count();

                    $successCount += $chunkSuccess;
                    $failureCount += $chunkFailure;

                    Log::info("✅ Chunk {$chunkIndex}: {$chunkSuccess} éxitos, {$chunkFailure} fallos");

                    // Manejar tokens inválidos
                    foreach ($report->failures()->getItems() as $failure) {
                        $error = $failure->error();
                        $token = $failure->target()->value();

                        Log::warning("❌ Token inválido: " . substr($token, 0, 20) . "... - " . $error->getMessage());

                        // Eliminar token inválido de la tabla cliente_dispositivos
                        $this->eliminarTokenInvalido($token);
                    }

                    // Log de tokens exitosos (opcional)
                    foreach ($report->successes()->getItems() as $success) {
                        try {
                            $token = $success->target()->value();
                            Log::debug("✅ Enviado a: " . substr($token, 0, 20) . "...");
                        } catch (\Throwable $e) {
                            // algunos items pueden no exponer target de la misma forma; ignorar problemas menores de logging
                        }
                    }
                } catch (MessagingException $e) {
                    Log::error("💥 Error en chunk {$chunkIndex}: " . $e->getMessage());
                    $failureCount += count($chunk);
                }
            }

            Log::info("🎯 RESUMEN FINAL: {$successCount} éxitos, {$failureCount} fallos");
            return $successCount > 0;
        } catch (\Throwable $e) {
            Log::error("💥 ERROR CRÍTICO: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Eliminar token inválido (ahora en cliente_dispositivos)
     */
    private function eliminarTokenInvalido(string $token): void
    {
        try {
            $affected = ClienteDispositivo::where('fcm_token', $token)->delete();
            if ($affected > 0) {
                Log::info("🗑️ Token eliminado de cliente_dispositivos: " . substr($token, 0, 20) . "...");
            }
        } catch (\Exception $e) {
            Log::error("❌ Error eliminando token: " . $e->getMessage());
        }
    }

    /**
     * Probar notificación específica (envía a un token)
     */
    public function probarNotificacion($token, $mangaId = 1, $numeroTomo = 1): array
    {
        Log::info("🧪 PROBANDO NOTIFICACIÓN - Token: " . substr($token, 0, 20) . "...");

        if (!$this->messaging) {
            Log::error("❌ Firebase Messaging no inicializado");
            return ['error' => 'Firebase no inicializado'];
        }

        if (empty($token)) {
            Log::error("❌ Token vacío");
            return ['error' => 'Token vacío'];
        }

        $mangaTitulo = \App\Models\Manga::find($mangaId)->titulo ?? "Manga #{$mangaId}";
        $title = "🧪 Notificación de prueba";
        $body = "{$mangaTitulo} - Tomo #{$numeroTomo} - Prueba del sistema";

        $data = [
            'manga_id' => (string)$mangaId,
            'numero_tomo' => (string)$numeroTomo,
            'type' => 'test',
            'timestamp' => now()->toISOString()
        ];

        try {
            Log::info("🛠️ Creando notificación de prueba...");
            $notification = Notification::create($title, $body);

            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withData($data)
                ->withHighestPossiblePriority();

            Log::info("🚀 Enviando mensaje de prueba usando sendMulticast...");
            $report = $this->messaging->sendMulticast($message, [$token]);

            $successCount = $report->successes()->count();
            $failureCount = $report->failures()->count();

            if ($successCount > 0) {
                $messageId = $report->successes()->getItems()[0]->messageId() ?? 'unknown';
                Log::info("✅ ✅ ✅ PRUEBA EXITOSA - Message ID: {$messageId}");

                return [
                    'success' => true,
                    'message_id' => $messageId,
                    'tokens_enviados' => 1,
                    'method' => 'sendMulticast'
                ];
            } else {
                $errorMessage = 'Error desconocido';
                if ($failureCount > 0) {
                    $error = $report->failures()->getItems()[0]->error();
                    $errorMessage = $error->getMessage();
                    Log::error("❌ Error en envío: " . $errorMessage);
                }

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'tokens_enviados' => 0
                ];
            }
        } catch (MessagingException $e) {
            Log::error("❌ MessagingException: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        } catch (\Throwable $e) {
            Log::error("❌ Error inesperado: " . $e->getMessage());
            return ['success' => false, 'error' => 'Error inesperado: ' . $e->getMessage()];
        }
    }

    /**
     * Verificar validez del token
     */
    public function verificarToken($token): array
    {
        Log::info("🔍 VERIFICANDO TOKEN: " . substr($token, 0, 20) . '...');

        if (!$this->messaging) {
            return ['valid' => false, 'error' => 'Firebase no inicializado'];
        }

        try {
            $message = CloudMessage::new()
                ->withData(['test' => 'true', 'timestamp' => now()->toISOString()])
                ->withHighestPossiblePriority();

            $report = $this->messaging->sendMulticast($message, [$token]);

            if ($report->successes()->count() > 0) {
                return ['valid' => true, 'message' => 'Token válido'];
            } else {
                $error = $report->failures()->getItems()[0]->error();
                return ['valid' => false, 'error' => $error->getMessage(), 'message' => 'Token inválido'];
            }
        } catch (\Throwable $e) {
            return ['valid' => false, 'error' => $e->getMessage(), 'message' => 'Error verificando token'];
        }
    }

    /**
     * Debug completo del estado de notificaciones
     */
    public function debugEstadoNotificaciones($clienteId = null)
    {
        try {
            $estado = [
                'firebase_initialized' => !is_null($this->messaging),
                'credentials_path' => $this->credentialsPath,
                'credentials_exists' => $this->credentialsPath ? file_exists($this->credentialsPath) : false,
                'environment' => app()->environment(),
                'timestamp' => now()->toISOString()
            ];

            if ($clienteId) {
                $suscripciones = ClienteMangaSuscripcion::where('cliente_id', $clienteId)
                    ->get()
                    ->groupBy('manga_id');

                $dispositivos = ClienteDispositivo::where('cliente_id', $clienteId)->get()->map(function ($d) {
                    return [
                        'token' => substr($d->fcm_token, 0, 20) . '...',
                        'platform' => $d->platform,
                        'last_active_at' => $d->last_active_at,
                    ];
                });

                $estado['cliente'] = [
                    'id' => $clienteId,
                    'suscripciones_count' => $suscripciones->count(),
                    'dispositivos' => $dispositivos,
                ];
            }

            Log::info('🔍 DEBUG ESTADO NOTIFICACIONES: ' . json_encode($estado));

            return $estado;
        } catch (\Exception $e) {
            Log::error('❌ Error en debugEstadoNotificaciones: ' . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }
}

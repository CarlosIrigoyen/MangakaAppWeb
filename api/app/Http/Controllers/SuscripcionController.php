<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\ClienteMangaSuscripcion;
use App\Models\Manga;
use App\Models\ClienteDispositivo;

class SuscripcionController extends Controller
{
    /**
     * Obtener mangas disponibles para suscripción
     */
    public function mangasDisponibles()
    {
        try {
            $mangas = Manga::with('autor')
                ->activo()
                ->get()
                ->map(function ($manga) {
                    return [
                        'id' => $manga->id,
                        'titulo' => $manga->titulo,
                        'autor' => $manga->autor ? [
                            'nombre' => $manga->autor->nombre,
                            'apellido' => $manga->autor->apellido
                        ] : null
                    ];
                });

            return response()->json([
                'success' => true,
                'mangas' => $mangas
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo mangas disponibles: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar mangas disponibles'
            ], 500);
        }
    }

    /**
     * Obtener suscripciones actuales del usuario
     */
    public function misSuscripciones(Request $request)
    {
        try {
            $cliente = $request->user();

            $mangasSuscritos = ClienteMangaSuscripcion::where('cliente_id', $cliente->id)
                ->with('manga')
                ->get()
                ->pluck('manga.id')
                ->toArray();

            return response()->json([
                'success' => true,
                'mangas_suscritos' => $mangasSuscritos
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo suscripciones: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar suscripciones'
            ], 500);
        }
    }

    /**
     * Actualizar suscripciones del usuario (se guardan por cliente)
     * Request: { mangas_seleccionados: [1,2,3] }
     */
    public function actualizarSuscripciones(Request $request)
    {
        $request->validate([
            'mangas_seleccionados' => 'required|array'
        ]);

        $cliente = $request->user();
        $mangasSeleccionados = $request->mangas_seleccionados;

        Log::info("🔔 Actualizando suscripciones para cliente: {$cliente->id}");
        Log::info("📋 Mangas seleccionados: " . json_encode($mangasSeleccionados));

        try {
            // Reemplazamos todas las suscripciones del cliente
            ClienteMangaSuscripcion::where('cliente_id', $cliente->id)->delete();

            $suscripcionesCreadas = 0;
            foreach ($mangasSeleccionados as $mangaId) {
                ClienteMangaSuscripcion::firstOrCreate([
                    'cliente_id' => $cliente->id,
                    'manga_id' => $mangaId
                ]);
                $suscripcionesCreadas++;
            }

            Log::info("✅ Suscripciones actualizadas. Creadas: " . $suscripcionesCreadas);

            return response()->json([
                'success' => true,
                'message' => 'Suscripciones actualizadas correctamente',
                'suscripciones_creadas' => $suscripcionesCreadas
            ]);
        } catch (\Exception $e) {
            Log::error('Error actualizando suscripciones: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar suscripciones'
            ], 500);
        }
    }

    /**
     * Suscribir a un manga específico (individual)
     * Request: { manga_id: 1 }
     */
    public function suscribir(Request $request)
    {
        $request->validate([
            'manga_id' => 'required|exists:mangas,id'
        ]);

        $cliente = $request->user();

        try {
            $suscripcion = ClienteMangaSuscripcion::firstOrCreate([
                'cliente_id' => $cliente->id,
                'manga_id' => $request->manga_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Suscripción creada correctamente',
                'suscripcion' => $suscripcion
            ]);
        } catch (\Exception $e) {
            Log::error('Error en suscripción individual: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al suscribirse'
            ], 500);
        }
    }

    /**
     * Desuscribir de un manga (individual)
     * Request: { manga_id: 1 }
     */
    public function desuscribir(Request $request)
    {
        $request->validate([
            'manga_id' => 'required|exists:mangas,id'
        ]);

        $cliente = $request->user();

        try {
            $eliminados = ClienteMangaSuscripcion::where('cliente_id', $cliente->id)
                ->where('manga_id', $request->manga_id)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Desuscripción realizada correctamente',
                'eliminados' => $eliminados
            ]);
        } catch (\Exception $e) {
            Log::error('Error en desuscripción: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al desuscribirse'
            ], 500);
        }
    }

    /**
     * Registrar token FCM de un dispositivo para el cliente actual.
     * Request: { fcm_token: string, platform?: string }
     */
    public function registrarToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
            'platform' => 'nullable|string'
        ]);

        $cliente = $request->user();
        $fcmToken = $request->fcm_token;
        $platform = $request->platform ?? null;

        Log::info("🔑 Registrar token para cliente {$cliente->id}: " . substr($fcmToken, 0, 20) . "...");

        try {
            $device = ClienteDispositivo::where('fcm_token', $fcmToken)->first();

            if ($device) {
                // reasignar si pertenece a otro cliente o actualizar last_active
                if ($device->cliente_id !== $cliente->id) {
                    $device->update([
                        'cliente_id' => $cliente->id,
                        'platform' => $platform,
                        'last_active_at' => now()
                    ]);
                } else {
                    $device->update([
                        'last_active_at' => now(),
                        'platform' => $platform
                    ]);
                }
            } else {
                ClienteDispositivo::create([
                    'cliente_id' => $cliente->id,
                    'fcm_token' => $fcmToken,
                    'platform' => $platform,
                    'last_active_at' => now()
                ]);
            }

            return response()->json(['success' => true, 'message' => 'Token registrado']);
        } catch (\Exception $e) {
            Log::error('Error registrando token: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error registrando token'], 500);
        }
    }

    /**
     * Eliminar token de dispositivo (por ejemplo en logout).
     * Request: { fcm_token: string }
     */
    public function eliminarToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string'
        ]);

        $cliente = $request->user();
        $fcmToken = $request->fcm_token;

        try {
            $eliminados = ClienteDispositivo::where('cliente_id', $cliente->id)
                ->where('fcm_token', $fcmToken)
                ->delete();

            return response()->json(['success' => true, 'eliminados' => $eliminados]);
        } catch (\Exception $e) {
            Log::error('Error eliminando token: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error eliminando token'], 500);
        }
    }

    /**
     * Obtener un token existente del cliente (primero que encuentre)
     * Útil para compatibilidad con frontend que espera un token "existente".
     */
    public function obtenerTokenAutomatico(Request $request)
    {
        $cliente = $request->user();

        try {
            $tokenExistente = ClienteDispositivo::where('cliente_id', $cliente->id)
                ->whereNotNull('fcm_token')
                ->value('fcm_token');

            return response()->json([
                'success' => true,
                'token_existente' => $tokenExistente,
                'message' => $tokenExistente ? 'Token recuperado' : 'No hay token existente'
            ]);
        } catch (\Exception $e) {
            Log::error('Error obteniendo token automático: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al recuperar token'
            ], 500);
        }
    }
}

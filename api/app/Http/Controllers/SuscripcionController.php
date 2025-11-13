<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\ClienteMangaSuscripcion;
use App\Models\Manga;

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
                ->whereNotNull('fcm_token')
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
     * Actualizar suscripciones del usuario
     */
    public function actualizarSuscripciones(Request $request)
    {
        $request->validate([
            'mangas_seleccionados' => 'required|array',
            'fcm_token' => 'required|string'
        ]);

        $cliente = $request->user();
        $mangasSeleccionados = $request->mangas_seleccionados;
        $fcmToken = $request->fcm_token;

        Log::info("🔔 Actualizando suscripciones para cliente: {$cliente->id}");
        Log::info("📋 Mangas seleccionados: " . json_encode($mangasSeleccionados));
        Log::info("🔑 Token: " . substr($fcmToken, 0, 20) . '...');

        try {
            // 1. Eliminar suscripciones existentes para este token y cliente
            ClienteMangaSuscripcion::where('cliente_id', $cliente->id)
                ->where('fcm_token', $fcmToken)
                ->delete();

            // 2. Crear nuevas suscripciones
            $suscripcionesCreadas = [];
            foreach ($mangasSeleccionados as $mangaId) {
                $suscripcion = ClienteMangaSuscripcion::create([
                    'cliente_id' => $cliente->id,
                    'manga_id' => $mangaId,
                    'fcm_token' => $fcmToken
                ]);

                $suscripcionesCreadas[] = $suscripcion->id;
            }

            Log::info("✅ Suscripciones actualizadas. Creadas: " . count($suscripcionesCreadas));

            return response()->json([
                'success' => true,
                'message' => 'Suscripciones actualizadas correctamente',
                'suscripciones_creadas' => count($suscripcionesCreadas)
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
     * Manejo automático de suscripciones con token
     */
    public function manejarSuscripcionAutomatica(Request $request)
    {
        $request->validate([
            'mangas_seleccionados' => 'required|array',
            'fcm_token' => 'required|string'
        ]);

        $cliente = $request->user();
        $mangasSeleccionados = $request->mangas_seleccionados;
        $fcmToken = $request->fcm_token;

        Log::info("🔄 Manejo automático de suscripción para cliente: {$cliente->id}");

        try {
            // 1. Buscar tokens antiguos del usuario y migrar suscripciones
            $this->migrarSuscripcionesAntiguas($cliente->id, $fcmToken);

            // 2. Eliminar suscripciones existentes para este token específico
            ClienteMangaSuscripcion::where('cliente_id', $cliente->id)
                ->where('fcm_token', $fcmToken)
                ->delete();

            // 3. Crear nuevas suscripciones
            $suscripcionesCreadas = [];
            foreach ($mangasSeleccionados as $mangaId) {
                $suscripcion = ClienteMangaSuscripcion::create([
                    'cliente_id' => $cliente->id,
                    'manga_id' => $mangaId,
                    'fcm_token' => $fcmToken
                ]);
                $suscripcionesCreadas[] = $suscripcion->id;
            }

            Log::info("✅ Suscripciones automáticas actualizadas. Creadas: " . count($suscripcionesCreadas));

            return response()->json([
                'success' => true,
                'message' => 'Suscripciones actualizadas automáticamente',
                'suscripciones_creadas' => count($suscripcionesCreadas)
            ]);

        } catch (\Exception $e) {
            Log::error('Error en suscripción automática: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al manejar suscripciones automáticas'
            ], 500);
        }
    }

    /**
     * Migrar suscripciones de tokens antiguos al nuevo token
     */
    private function migrarSuscripcionesAntiguas($clienteId, $nuevoToken)
    {
        try {
            // Buscar todos los tokens del cliente excepto el nuevo
            $tokensAntiguos = ClienteMangaSuscripcion::where('cliente_id', $clienteId)
                ->where('fcm_token', '!=', $nuevoToken)
                ->whereNotNull('fcm_token')
                ->pluck('fcm_token')
                ->unique()
                ->values()
                ->toArray();

            if (empty($tokensAntiguos)) {
                return;
            }

            Log::info("🔄 Migrando suscripciones de " . count($tokensAntiguos) . " tokens antiguos");

            // Para cada token antiguo, migrar sus mangas al nuevo token
            foreach ($tokensAntiguos as $tokenAntiguo) {
                $mangasDelToken = ClienteMangaSuscripcion::where('cliente_id', $clienteId)
                    ->where('fcm_token', $tokenAntiguo)
                    ->pluck('manga_id')
                    ->toArray();

                // Crear suscripciones con el nuevo token
                foreach ($mangasDelToken as $mangaId) {
                    ClienteMangaSuscripcion::firstOrCreate([
                        'cliente_id' => $clienteId,
                        'manga_id' => $mangaId,
                        'fcm_token' => $nuevoToken
                    ]);
                }

                // Eliminar el token antiguo (opcional, puedes comentar esto si quieres mantener historial)
                ClienteMangaSuscripcion::where('cliente_id', $clienteId)
                    ->where('fcm_token', $tokenAntiguo)
                    ->delete();
            }

            Log::info("✅ Migración completada");

        } catch (\Exception $e) {
            Log::error('Error migrando suscripciones antiguas: ' . $e->getMessage());
        }
    }

    /**
     * Obtener o crear token automáticamente
     */
    public function obtenerTokenAutomatico(Request $request)
    {
        $cliente = $request->user();

        try {
            // Buscar cualquier token existente del usuario
            $tokenExistente = ClienteMangaSuscripcion::where('cliente_id', $cliente->id)
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

    /**
     * Actualizar el token FCM para todas las suscripciones del cliente
     * VERSIÓN MEJORADA - Maneja migración completa de tokens
     */
    public function actualizarToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string'
        ]);

        $cliente = $request->user();
        $nuevoToken = $request->fcm_token;

        Log::info("🔄 ACTUALIZAR TOKEN MEJORADO - Cliente: {$cliente->id}");
        Log::info("🔑 Nuevo token: " . substr($nuevoToken, 0, 20) . '...');

        try {
            // 1. Buscar todos los tokens antiguos del cliente
            $tokensAntiguos = ClienteMangaSuscripcion::where('cliente_id', $cliente->id)
                ->where('fcm_token', '!=', $nuevoToken)
                ->whereNotNull('fcm_token')
                ->pluck('fcm_token')
                ->unique()
                ->values()
                ->toArray();

            Log::info("📋 Tokens antiguos encontrados: " . count($tokensAntiguos));

            $suscripcionesActualizadas = 0;

            if (!empty($tokensAntiguos)) {
                // 2. Migrar TODAS las suscripciones al nuevo token
                foreach ($tokensAntiguos as $tokenAntiguo) {
                    Log::info("🔄 Migrando suscripciones desde: " . substr($tokenAntiguo, 0, 20) . '...');

                    // Obtener todas las suscripciones del token antiguo
                    $suscripcionesAntiguas = ClienteMangaSuscripcion::where('cliente_id', $cliente->id)
                        ->where('fcm_token', $tokenAntiguo)
                        ->get();

                    foreach ($suscripcionesAntiguas as $suscripcion) {
                        // Crear nueva suscripción con el nuevo token (o actualizar si existe)
                        ClienteMangaSuscripcion::updateOrCreate(
                            [
                                'cliente_id' => $cliente->id,
                                'manga_id' => $suscripcion->manga_id,
                                'fcm_token' => $nuevoToken
                            ],
                            [] // No hay campos adicionales para actualizar
                        );

                        $suscripcionesActualizadas++;
                    }

                    // Eliminar las suscripciones con el token antiguo
                    ClienteMangaSuscripcion::where('cliente_id', $cliente->id)
                        ->where('fcm_token', $tokenAntiguo)
                        ->delete();

                    Log::info("✅ Migradas {$suscripcionesAntiguas->count()} suscripciones");
                }

                Log::info("🎯 TOTAL: {$suscripcionesActualizadas} suscripciones migradas al nuevo token");
            } else {
                Log::info("ℹ️ No hay tokens antiguos que migrar");

                // Verificar si el nuevo token ya tiene suscripciones
                $suscripcionesExistentes = ClienteMangaSuscripcion::where('cliente_id', $cliente->id)
                    ->where('fcm_token', $nuevoToken)
                    ->count();

                if ($suscripcionesExistentes === 0) {
                    Log::info("➕ No se crea suscripción base porque el usuario no tenía ninguna");
                } else {
                    Log::info("✅ El token nuevo ya tiene {$suscripcionesExistentes} suscripciones");
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Token actualizado y suscripciones migradas correctamente',
                'suscripciones_actualizadas' => $suscripcionesActualizadas,
                'tokens_migrados' => count($tokensAntiguos)
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Error en actualizarToken: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar token: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar suscripciones duplicadas para el mismo cliente, manga y token
     */
    private function eliminarDuplicados($clienteId, $token)
    {
        try {
            // Encontrar duplicados y mantener solo el más reciente
            $duplicados = ClienteMangaSuscripcion::where('cliente_id', $clienteId)
                ->where('fcm_token', $token)
                ->select('manga_id', DB::raw('COUNT(*) as count'))
                ->groupBy('manga_id')
                ->having('count', '>', 1)
                ->get();

            if ($duplicados->count() > 0) {
                Log::info("🧹 Eliminando duplicados para {$duplicados->count()} mangas");

                foreach ($duplicados as $duplicado) {
                    // Mantener solo el registro más reciente
                    $suscripciones = ClienteMangaSuscripcion::where('cliente_id', $clienteId)
                        ->where('manga_id', $duplicado->manga_id)
                        ->where('fcm_token', $token)
                        ->orderBy('created_at', 'desc')
                        ->get();

                    // Eliminar todos excepto el más reciente
                    if ($suscripciones->count() > 1) {
                        $mantener = $suscripciones->first();
                        $eliminar = $suscripciones->slice(1);

                        ClienteMangaSuscripcion::whereIn('id', $eliminar->pluck('id'))->delete();
                        Log::info("✅ Eliminados " . $eliminar->count() . " duplicados para manga {$duplicado->manga_id}");
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("❌ Error eliminando duplicados: " . $e->getMessage());
        }
    }

    /**
     * Suscribir a un manga específico
     */
    public function suscribir(Request $request)
    {
        $request->validate([
            'manga_id' => 'required|exists:mangas,id',
            'fcm_token' => 'required|string'
        ]);

        $cliente = $request->user();

        try {
            // Usar updateOrCreate para evitar duplicados
            $suscripcion = ClienteMangaSuscripcion::updateOrCreate(
                [
                    'cliente_id' => $cliente->id,
                    'manga_id' => $request->manga_id,
                    'fcm_token' => $request->fcm_token
                ],
                [] // No hay campos adicionales para actualizar
            );

            return response()->json([
                'success' => true,
                'message' => 'Suscripción actualizada correctamente',
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
     * Desuscribir de un manga
     */
    public function desuscribir(Request $request)
    {
        $request->validate([
            'manga_id' => 'required|exists:mangas,id',
            'fcm_token' => 'required|string'
        ]);

        $cliente = $request->user();

        try {
            $eliminados = ClienteMangaSuscripcion::where('cliente_id', $cliente->id)
                ->where('manga_id', $request->manga_id)
                ->where('fcm_token', $request->fcm_token)
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
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    /**
     * Maneja la autenticación con Google
     * Endpoint: POST /api/auth/google
     */
    public function authenticate(Request $request)
    {
        Log::info('🔐 Iniciando autenticación Google', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        // 1. Validar request
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
        ]);

        if ($validator->fails()) {
            Log::warning('❌ Validación fallida', $validator->errors()->toArray());
            return response()->json([
                'success' => false,
                'message' => 'Token de Google es requerido',
                'errors' => $validator->errors()
            ], 422);
        }

        $googleToken = $request->input('token');

        try {
            // 2. Configurar cliente Google
            $client = new GoogleClient([
                'client_id' => env('GOOGLE_CLIENT_ID'),
                'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            ]);

            // 3. Verificar token ID de Google
            Log::debug('🔍 Verificando token Google');
            $payload = $client->verifyIdToken($googleToken);

            if (!$payload) {
                Log::warning('❌ Token Google inválido');
                return response()->json([
                    'success' => false,
                    'message' => 'Token de Google inválido o expirado'
                ], 401);
            }

            // 4. Extraer datos del usuario
            $googleId = $payload['sub'];
            $email = $payload['email'];
            $nombre = $payload['name'] ?? $email;
            $avatar = $payload['picture'] ?? null;

            Log::info('👤 Datos Google obtenidos', [
                'email' => $email,
                'google_id' => $googleId,
                'name' => $nombre
            ]);

            // 5. Buscar o crear usuario
            $cliente = $this->findOrCreateCliente([
                'google_id' => $googleId,
                'email' => $email,
                'nombre' => $nombre,
                'avatar' => $avatar
            ]);

            if (!$cliente) {
                Log::error('❌ Error al crear/obtener cliente');
                return response()->json([
                    'success' => false,
                    'message' => 'Error al procesar usuario'
                ], 500);
            }

            // 6. Crear token de acceso
            Log::debug('🔑 Creando token Sanctum');
            $token = $cliente->createToken('google-auth-token', ['*'], now()->addDays(30))->plainTextToken;

            // 7. Preparar respuesta
            $response = [
                'success' => true,
                'message' => '¡Autenticación exitosa!',
                'cliente' => [
                    'id' => $cliente->id,
                    'nombre' => $cliente->nombre,
                    'email' => $cliente->email,
                    'direccion' => $cliente->direccion,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => 30 * 24 * 60 * 60 // 30 días en segundos
            ];

            Log::info('✅ Autenticación Google exitosa', [
                'cliente_id' => $cliente->id,
                'email' => $cliente->email
            ]);

            return response()->json($response, 200);

        } catch (\Exception $e) {
            Log::error('🔥 Error en autenticación Google', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Encuentra o crea un cliente basado en datos de Google
     */
    private function findOrCreateCliente($googleData)
    {
        try {
            // Buscar por google_id primero
            $cliente = Cliente::where('google_id', $googleData['google_id'])->first();

            if ($cliente) {
                Log::info('🔄 Cliente encontrado por google_id', ['id' => $cliente->id]);
                return $cliente;
            }

            // Buscar por email
            $cliente = Cliente::where('email', $googleData['email'])->first();

            if ($cliente) {
                Log::info('📧 Cliente encontrado por email, actualizando google_id', ['id' => $cliente->id]);

                // Verificar que no tenga otro google_id
                if (!empty($cliente->google_id) && $cliente->google_id !== $googleData['google_id']) {
                    Log::warning('⚠️ Conflicto: Email ya tiene otro google_id', [
                        'email' => $googleData['email'],
                        'existing_google_id' => $cliente->google_id,
                        'new_google_id' => $googleData['google_id']
                    ]);

                    // En este caso, podrías:
                    // 1. Rechazar el login
                    // 2. Pedir que se una cuentas
                    // Aquí elegimos actualizar (sobrescribir)
                }

                $cliente->google_id = $googleData['google_id'];
                $cliente->save();

                return $cliente;
            }

            // Crear nuevo cliente
            Log::info('🆕 Creando nuevo cliente desde Google');

            $cliente = Cliente::create([
                'nombre' => $googleData['nombre'],
                'email' => $googleData['email'],
                'google_id' => $googleData['google_id'],
                'password' => Hash::make(Str::random(32)), // Password aleatorio seguro
                'direccion' => 'Por definir - Actualiza tu dirección en el perfil',
            ]);

            return $cliente;

        } catch (\Exception $e) {
            Log::error('❌ Error en findOrCreateCliente', [
                'error' => $e->getMessage(),
                'data' => $googleData
            ]);
            return null;
        }
    }

    /**
     * Endpoint para probar conexión
     */
    public function testConnection()
    {
        return response()->json([
            'success' => true,
            'message' => 'Google Auth Controller está funcionando',
            'timestamp' => now()->toDateTimeString(),
            'google_client_id_set' => !empty(env('GOOGLE_CLIENT_ID'))
        ]);
    }
}

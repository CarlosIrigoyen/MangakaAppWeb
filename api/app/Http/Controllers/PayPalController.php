<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Factura;
use App\Models\DetalleFactura;
use App\Models\Tomo;

class PayPalController extends Controller
{
    private $paypalBaseUrl;
    private $clientId;
    private $clientSecret;

    public function __construct()
    {
        // FORZAR sandbox aquí solo para pruebas; en producción cambiar a https://api.paypal.com
        $this->paypalBaseUrl = env('PAYPAL_BASE_URL', 'https://api.sandbox.paypal.com');
        $this->clientId = env('PAYPAL_CLIENT_ID');
        $this->clientSecret = env('PAYPAL_CLIENT_SECRET');
    }

    /**
     * Obtiene access token OAuth2 de PayPal.
     */
    private function getAccessToken()
    {
        try {
            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->asForm()
                ->post("{$this->paypalBaseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials'
                ]);

            if ($response->successful()) {
                $json = $response->json();
                return $json['access_token'] ?? null;
            }

            Log::error('getAccessToken failed, body: ' . $response->body());
            throw new \Exception('No se pudo obtener access token: ' . $response->body());
        } catch (\Exception $e) {
            Log::error('Error en getAccessToken: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Crea la orden en PayPal. Guarda metadata completa en BD y envía custom_id corto a PayPal.
     * En caso de stock insuficiente devuelve JSON con { message, tomo_id } y HTTP 400 (o 404 si no existe).
     */
    public function createOrder(Request $request)
    {
        Log::info("📥 createOrder PayPal payload recibido: ", $request->all());

        try {
            $request->validate([
                'cliente_id' => 'required|integer|exists:clientes,id',
                'productos' => 'required|array|min:1',
                'productos.*.tomo_id' => 'required|integer|exists:tomos,id',
                'productos.*.titulo' => 'required|string',
                'productos.*.cantidad' => 'required|integer|min:1',
                'productos.*.precio_unitario' => 'required|numeric|min:0',
            ]);

            $clienteId = $request->input('cliente_id');
            $productos = $request->input('productos', []);

            // 1) Validar stock -> si falta stock devolver JSON estructurado con tomo_id
            foreach ($productos as $prod) {
                $tomo = Tomo::find($prod['tomo_id']);
                if (!$tomo) {
                    return response()->json([
                        'message' => "Tomo no encontrado",
                        'tomo_id' => $prod['tomo_id']
                    ], 404);
                }
                if ($tomo->stock < $prod['cantidad']) {
                    return response()->json([
                        'message' => "Stock insuficiente para el tomo ID {$prod['tomo_id']}",
                        'tomo_id' => $prod['tomo_id'],
                        'stock' => $tomo->stock
                    ], 400);
                }
            }

            // 2) Preparar items y calcular total
            $totalAmount = 0;
            $items = [];

            foreach ($productos as $prod) {
                $cantidad = (int) $prod['cantidad'];
                $precioUnitario = (float) $prod['precio_unitario'];
                $subtotal = $cantidad * $precioUnitario;
                $totalAmount += $subtotal;

                $items[] = [
                    'name' => substr($prod['titulo'], 0, 127),
                    'quantity' => (string) $cantidad,
                    'unit_amount' => [
                        'currency_code' => 'USD',
                        'value' => number_format($precioUnitario, 2, '.', '')
                    ],
                    'category' => 'DIGITAL_GOODS'
                ];
            }

            // 3) Guardar metadata completa en BD
            $metaId = DB::table('orden_metadata')->insertGetId([
                'cliente_id' => $clienteId,
                'productos' => json_encode($productos, JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 4) custom_id corto y seguro para PayPal
            $customId = "meta_{$metaId}";
            if (strlen($customId) > 127) {
                $customId = substr($customId, 0, 127);
            }

            // 5) Armar payload de la orden
            $accessToken = $this->getAccessToken();
            $frontendUrl = env('APP_FRONTEND_URL', 'https://mangakaappwebfront-production.up.railway.app');

            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => 'cliente_' . $clienteId,
                        'description' => 'Compra de mangas - MangakaBaka',
                        'custom_id' => $customId,
                        'amount' => [
                            'currency_code' => 'USD',
                            'value' => number_format($totalAmount, 2, '.', ''),
                            'breakdown' => [
                                'item_total' => [
                                    'currency_code' => 'USD',
                                    'value' => number_format($totalAmount, 2, '.', '')
                                ]
                            ]
                        ],
                        'items' => $items
                    ]
                ],
                'application_context' => [
                    'return_url' => $frontendUrl . '/paypal-return',
                    'cancel_url' => $frontendUrl . '/cart',
                    'brand_name' => 'MangakaBaka Store',
                    'user_action' => 'PAY_NOW',
                    'shipping_preference' => 'NO_SHIPPING'
                ]
            ];

            // LOG para depuración: comprobar que custom_id es corto (ej: "meta_123")
            Log::info('🧾 PayPal order payload (antes de enviar): ' . json_encode($orderData, JSON_UNESCAPED_UNICODE));

            $response = Http::withToken($accessToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Prefer' => 'return=representation'
                ])
                ->post("{$this->paypalBaseUrl}/v2/checkout/orders", $orderData);

            $responseData = $response->json();

            if (!$response->successful()) {
                Log::error('PayPal create-order error body: ' . $response->body());
                throw new \Exception('Error PayPal: ' . $response->body());
            }

            $approveLink = collect($responseData['links'] ?? [])->firstWhere('rel', 'approve');
            if (!$approveLink) {
                Log::error('No approve link in PayPal response: ' . json_encode($responseData));
                throw new \Exception('No se encontró el link de aprobación');
            }

            Log::info("✅ Orden PayPal creada - ID: {$responseData['id']} - metaId: {$metaId}");

            return response()->json([
                'id' => $responseData['id'],
                'status' => $responseData['status'],
                'approve_url' => $approveLink['href'],
                'sandbox_mode' => str_contains($this->paypalBaseUrl, 'sandbox'),
                'message' => 'Orden creada. Redirigir a approve_url.'
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error en createOrder: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error creando la orden de PayPal: ' . $e->getMessage(),
                'sandbox_mode' => str_contains($this->paypalBaseUrl, 'sandbox')
            ], 500);
        }
    }

    /**
     * Captura una orden por orderId (usado por frontend o por procesos internos).
     */
    public function captureOrder($orderId)
    {
        DB::beginTransaction();
        try {
            $accessToken = $this->getAccessToken();

            Log::info("🎯 Capturando orden PayPal: " . $orderId);

            $url = "{$this->paypalBaseUrl}/v2/checkout/orders/{$orderId}/capture";

            $response = Http::withToken($accessToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Prefer' => 'return=representation'
                ])
                ->withBody('{}', 'application/json')
                ->post($url);

            Log::info("PayPal capture HTTP status: " . $response->status());
            Log::info("PayPal capture response body: " . $response->body());

            $captureData = $response->json();

            if (!$response->successful()) {
                $errBody = $response->body();
                Log::error("PayPal capture error: HTTP {$response->status()} - {$errBody}");
                $errorData = json_decode($errBody, true);
                $friendlyMessage = $this->getFriendlyErrorMessage($errorData);
                throw new \Exception($friendlyMessage);
            }

            // Recuperar custom_id
            $customId = $captureData['purchase_units'][0]['custom_id'] ?? null;
            if (!$customId) {
                // Intentar otras rutas si aplica
                $customId = $captureData['purchase_units'][0]['payments']['captures'][0]['seller_receivable_breakdown']['custom_id'] ?? null;
            }

            if (!$customId || !str_starts_with($customId, 'meta_')) {
                throw new \Exception("No se pudo recuperar metadata de la orden (custom_id inválido).");
            }

            $metaId = (int) str_replace('meta_', '', $customId);

            // Leer metadata desde BD
            $metadata = DB::table('orden_metadata')->where('id', $metaId)->first();

            if (!$metadata) {
                throw new \Exception("Metadata no encontrada para la orden.");
            }

            $clienteId = $metadata->cliente_id;
            $productos = json_decode($metadata->productos, true);

            // Crear factura y detalles
            $factura = Factura::create([
                'numero' => 'PP-' . time() . '-' . Str::random(6),
                'cliente_id' => $clienteId,
                'pagado' => true,
            ]);

            foreach ($productos as $prod) {
                $subtotal = (float) $prod['cantidad'] * $prod['precio_unitario'];

                DetalleFactura::create([
                    'factura_id' => $factura->id,
                    'tomo_id' => $prod['tomo_id'],
                    'cantidad' => (int) $prod['cantidad'],
                    'precio_unitario' => (float) $prod['precio_unitario'],
                    'subtotal' => $subtotal,
                ]);

                $tomo = Tomo::find($prod['tomo_id']);
                if ($tomo) {
                    $stockAnterior = $tomo->stock;
                    $tomo->decrement('stock', $prod['cantidad']);
                    Log::info("📦 Stock actualizado - Tomo {$tomo->id}: {$stockAnterior} -> {$tomo->stock}");
                }
            }

            DB::commit();

            Log::info("✅ Factura {$factura->id} creada como PAGADA");

            return response()->json(array_merge($captureData, [
                'sandbox_mode' => str_contains($this->paypalBaseUrl, 'sandbox'),
                'factura_id' => $factura->id,
                'factura_numero' => $factura->numero
            ]));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error en captureOrder: ' . $e->getMessage());
            return response()->json([
                'message' => $e->getMessage(),
                'sandbox_mode' => str_contains($this->paypalBaseUrl, 'sandbox'),
                'error_type' => 'payment_failed'
            ], 500);
        }
    }

    /**
     * Webhook public endpoint para PayPal.
     * Requiere que configures PAYPAL_WEBHOOK_ID en .env con el ID del webhook creado en PayPal.
     * Verifica la firma mediante /v1/notifications/verify-webhook-signature
     */
    public function webhook(Request $request)
    {
        // Registrar cabeceras y body
        Log::info('🔔 PayPal webhook recibido', [
            'headers' => $request->headers->all(),
            'body' => $request->getContent()
        ]);

        try {
            $transmissionId = $request->header('PayPal-Transmission-Id');
            $transmissionTime = $request->header('PayPal-Transmission-Time');
            $certUrl = $request->header('PayPal-Cert-Url');
            $authAlgo = $request->header('PayPal-Auth-Algo');
            $transmissionSig = $request->header('PayPal-Transmission-Sig');
            $webhookId = env('PAYPAL_WEBHOOK_ID'); // asegurar que esté en .env

            if (!$webhookId) {
                Log::warning('PAYPAL_WEBHOOK_ID no configurado en .env');
                return response()->json(['message' => 'Webhook not configured'], 500);
            }

            $body = json_decode($request->getContent(), true);

            $verifyPayload = [
                'transmission_id' => $transmissionId,
                'transmission_time' => $transmissionTime,
                'cert_url' => $certUrl,
                'auth_algo' => $authAlgo,
                'transmission_sig' => $transmissionSig,
                'webhook_id' => $webhookId,
                'webhook_event' => $body
            ];

            $accessToken = $this->getAccessToken();

            $verifyResponse = Http::withToken($accessToken)
                ->post("{$this->paypalBaseUrl}/v1/notifications/verify-webhook-signature", $verifyPayload);

            $verifyJson = $verifyResponse->json();
            Log::info('🔍 PayPal webhook verify response: ' . json_encode($verifyJson));

            if (!($verifyResponse->successful() && ($verifyJson['verification_status'] ?? '') === 'SUCCESS')) {
                Log::warning('Webhook verification failed', ['verify' => $verifyJson]);
                return response()->json(['message' => 'Invalid webhook signature'], 400);
            }

            // Webhook verificado: manejar eventos importantes
            $eventType = $body['event_type'] ?? null;
            $resource = $body['resource'] ?? [];

            Log::info("Webhook event verified: {$eventType}");

            switch ($eventType) {
                case 'PAYMENT.CAPTURE.COMPLETED':
                case 'PAYMENT.CAPTURE.DENIED':
                case 'CHECKOUT.ORDER.APPROVED':
                case 'CHECKOUT.ORDER.COMPLETED':
                    // Intentamos obtener custom_id desde resource o purchase_units
                    $customId = null;

                    if (!empty($resource['custom_id'])) {
                        $customId = $resource['custom_id'];
                    } elseif (!empty($resource['supplementary_data']['related_ids']['order_id'])) {
                        // fallback (no siempre presente)
                        $customId = $resource['supplementary_data']['related_ids']['order_id'];
                    } elseif (!empty($resource['purchase_units'][0]['custom_id'])) {
                        $customId = $resource['purchase_units'][0]['custom_id'];
                    } elseif (!empty($resource['invoice_id'])) {
                        $customId = $resource['invoice_id'];
                    }

                    Log::info('Webhook extracted custom_id: ' . json_encode($customId));

                    // Si encontramos custom_id con prefijo meta_x intentamos procesar
                    if ($customId && is_string($customId) && str_starts_with($customId, 'meta_')) {
                        $metaId = (int) str_replace('meta_', '', $customId);
                        $metadata = DB::table('orden_metadata')->where('id', $metaId)->first();

                        if ($metadata) {
                            // Aquí podés implementar la misma lógica de captureOrder para crear la factura
                            // IMPORTANTE: hacerlo idempotente (verificar si ya existe factura para esta orden)
                            Log::info("Webhook: metadata encontrada para metaId {$metaId}");
                            // Ejemplo: podrías marcar la metadata como 'webhook_processed' en una columna adicional
                        } else {
                            Log::warning("Webhook: metadata NO encontrada para metaId {$metaId}");
                        }
                    } else {
                        Log::info('Webhook: custom_id no válido o no presente - no se procesa automáticamente.');
                    }

                    break;
                default:
                    Log::info("Webhook evento no manejado: {$eventType}");
            }

            return response()->json(['status' => 'ok']);
        } catch (\Exception $e) {
            Log::error('❌ Error en webhook PayPal: ' . $e->getMessage());
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Obtener orden (debug)
     */
    public function getOrder($orderId)
    {
        try {
            $accessToken = $this->getAccessToken();

            $response = Http::withToken($accessToken)
                ->get("{$this->paypalBaseUrl}/v2/checkout/orders/{$orderId}");

            return response()->json(array_merge($response->json(), ['sandbox_mode' => str_contains($this->paypalBaseUrl, 'sandbox')]));
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'sandbox_mode' => str_contains($this->paypalBaseUrl, 'sandbox')
            ], 500);
        }
    }

    /**
     * Devuelve config / debug info (útil en dev)
     */
    public function checkConfig()
    {
        return response()->json([
            'paypal_base_url' => $this->paypalBaseUrl,
            'client_id_prefix' => $this->clientId ? substr($this->clientId, 0, 10) . '...' : null,
            'mode' => str_contains($this->paypalBaseUrl, 'sandbox') ? 'SANDBOX' : 'LIVE',
            'status' => 'OK'
        ]);
    }

    public function debugConfig()
    {
        return $this->checkConfig();
    }

    /**
     * Maneja el retorno GET desde PayPal (frontend redirect)
     */
    public function handleReturn(Request $request)
    {
        $orderId = $request->query('token');
        $frontendUrl = env('APP_FRONTEND_URL', 'https://mangakaappwebfront-production.up.railway.app');

        if (!$orderId) {
            Log::warning('PayPal return called without token', $request->all());
            return redirect()->away($frontendUrl . '/cart');
        }

        return redirect()->away($frontendUrl . '/paypal-return?token=' . urlencode($orderId));
    }

    /**
     * Traduce errores del payload de PayPal a mensajes amigables.
     */
    private function getFriendlyErrorMessage($errorData)
    {
        if (!isset($errorData['details']) || !is_array($errorData['details'])) {
            return 'Error al procesar el pago. Por favor, intenta con otro método de pago.';
        }

        foreach ($errorData['details'] as $detail) {
            $issue = $detail['issue'] ?? '';

            switch ($issue) {
                case 'INSTRUMENT_DECLINED':
                    return 'La tarjeta fue rechazada. Por favor, intenta con otra tarjeta o método de pago.';
                case 'PAYER_CANNOT_PAY':
                    return 'Este método de pago no puede completar la transacción.';
                case 'TRANSACTION_REFUSED':
                    return 'La transacción fue rechazada.';
                case 'INSUFFICIENT_FUNDS':
                    return 'Fondos insuficientes.';
                case 'CVV_FAILURE':
                    return 'El código de seguridad es incorrecto.';
                case 'EXPIRED_CARD':
                    return 'La tarjeta ha expirado.';
                case '3D_SECURE_ERROR':
                    return 'Error en la verificación de seguridad.';
                default:
                    return 'Error al procesar el pago. Por favor, intenta nuevamente.';
            }
        }

        return 'Error al procesar el pago.';
    }
}

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
        // Forzar sandbox
        $this->paypalBaseUrl = 'https://api.sandbox.paypal.com';
        $this->clientId = env('PAYPAL_CLIENT_ID');
        $this->clientSecret = env('PAYPAL_CLIENT_SECRET');
    }

    private function getAccessToken()
    {
        try {
            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->asForm()
                ->post("{$this->paypalBaseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials'
                ]);

            if ($response->successful()) {
                return $response->json()['access_token'];
            }

            Log::error('getAccessToken failed, body: ' . $response->body());
            throw new \Exception('No se pudo obtener el access token: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('Error en getAccessToken: ' . $e->getMessage());
            throw $e;
        }
    }

    public function createOrder(Request $request)
    {
        Log::info("📥 createOrder PayPal: ", $request->all());

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

            // 1. Verificar stock (sin crear factura todavía)
            foreach ($productos as $prod) {
                $tomo = Tomo::find($prod['tomo_id']);
                if (!$tomo || $tomo->stock < $prod['cantidad']) {
                    throw new \Exception("Stock insuficiente para el tomo ID {$prod['tomo_id']}");
                }
            }

            // 2. Calcular total y preparar items
            $totalAmount = 0;
            $items = [];

            foreach ($productos as $prod) {
                $subtotal = (float) $prod['cantidad'] * $prod['precio_unitario'];
                $totalAmount += $subtotal;

                $items[] = [
                    'name' => substr($prod['titulo'], 0, 127),
                    'quantity' => (string) $prod['cantidad'],
                    'unit_amount' => [
                        'currency_code' => 'USD',
                        'value' => number_format($prod['precio_unitario'], 2, '.', '')
                    ],
                    'category' => 'DIGITAL_GOODS'
                ];
            }

            // 3. Crear orden en PayPal con metadata
            $accessToken = $this->getAccessToken();

            $frontendUrl = env('APP_FRONTEND_URL', 'https://mangakaappwebfront-production-b10c.up.railway.app');

            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => 'cliente_' . $clienteId,
                        'description' => 'Compra de mangas - MangakaBaka',
                        'custom_id' => json_encode([
                            'cliente_id' => $clienteId,
                            'productos' => $productos
                        ]),
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

            Log::info("✅ Orden PayPal creada - ID: {$responseData['id']}");

            return response()->json([
                'id' => $responseData['id'],
                'status' => $responseData['status'],
                'approve_url' => $approveLink['href'],
                'sandbox_mode' => true,
                'message' => 'MODO PRUEBAS - No se realizará cargo real'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error en createOrder: ' . $e->getMessage());

            return response()->json([
                'message' => 'Error creando la orden de PayPal: ' . $e->getMessage(),
                'sandbox_mode' => true
            ], 500);
        }
    }

    public function captureOrder($orderId)
    {
        DB::beginTransaction();
        try {
            $accessToken = $this->getAccessToken();

            Log::info("🎯 Capturando orden PayPal: " . $orderId);

            $response = Http::withToken($accessToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Prefer' => 'return=representation'
                ])
                ->post("{$this->paypalBaseUrl}/v2/checkout/orders/{$orderId}/capture");

            $captureData = $response->json();

            if (!$response->successful()) {
                Log::error('PayPal capture error body: ' . $response->body());
                throw new \Exception('Error capturando orden: ' . $response->body());
            }

            $status = $captureData['status'] ?? null;
            Log::info("PayPal capture status: {$status}");
            // Extraer metadata del custom_id
            $customId = $captureData['purchase_units'][0]['custom_id'] ?? null;
            $metadata = $customId ? json_decode($customId, true) : null;

            if (!$metadata) {
                // Intentar extraer metadata de captures si el custom_id no está en purchase_units
                $customIdAlt = $captureData['purchase_units'][0]['payments']['captures'][0]['custom_id'] ?? null;
                $metadata = $customIdAlt ? json_decode($customIdAlt, true) : null;
            }

            if (!$metadata) {
                Log::error('No se encontró metadata en la orden capture: ' . json_encode($captureData));
                throw new \Exception('No se encontró metadata en la orden');
            }

            $clienteId = $metadata['cliente_id'] ?? null;
            $productos = $metadata['productos'] ?? [];

            if (!$clienteId || empty($productos)) {
                Log::error('Metadata incompleta: ' . json_encode($metadata));
                throw new \Exception('Metadata incompleta');
            }

            // ✅ CREAR FACTURA SOLO AQUÍ CON PAGADO = TRUE
            $factura = Factura::create([
                'numero' => 'PP-' . time() . '-' . Str::random(6),
                'cliente_id' => $clienteId,
                'pagado' => true, // Directamente true
            ]);

            // Crear detalles y actualizar stock
            foreach ($productos as $prod) {
                $subtotal = (float) $prod['cantidad'] * $prod['precio_unitario'];

                DetalleFactura::create([
                    'factura_id' => $factura->id,
                    'tomo_id' => $prod['tomo_id'],
                    'cantidad' => (int) $prod['cantidad'],
                    'precio_unitario' => (float) $prod['precio_unitario'],
                    'subtotal' => $subtotal,
                ]);

                // Actualizar stock
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
                'sandbox_mode' => true,
                'factura_id' => $factura->id,
                'factura_numero' => $factura->numero
            ]));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error en captureOrder: ' . $e->getMessage());

            return response()->json([
                'message' => 'Error capturando el pago: ' . $e->getMessage(),
                'sandbox_mode' => true
            ], 500);
        }
    }

    public function webhook(Request $request)
    {
        Log::info('📥 Webhook PayPal recibido:', $request->all());

        $payload = $request->all();
        $eventType = $payload['event_type'] ?? null;

        Log::info("🔔 Evento PayPal: {$eventType}");

        try {
            if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
                return $this->handlePaymentCompleted($payload);
            }

            return response()->json(['status' => 'received'], 200);

        } catch (\Exception $e) {
            Log::error('❌ Error en webhook: ' . $e->getMessage());
            return response()->json(['error' => 'Processing failed'], 500);
        }
    }

    private function handlePaymentCompleted($payload)
    {
        $customId = $payload['resource']['custom_id'] ?? null;
        $metadata = $customId ? json_decode($customId, true) : null;

        if ($metadata) {
            $clienteId = $metadata['cliente_id'] ?? null;
            $productos = $metadata['productos'] ?? [];

            if ($clienteId && !empty($productos)) {
                DB::transaction(function () use ($clienteId, $productos) {
                    // ✅ CREAR FACTURA CON PAGADO = TRUE
                    $factura = Factura::create([
                        'numero' => 'PP-WH-' . time() . '-' . Str::random(6),
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
                            $tomo->decrement('stock', $prod['cantidad']);
                        }
                    }

                    Log::info("✅ Factura {$factura->id} creada como PAGADA via webhook");
                });

                return response()->json(['status' => 'success'], 200);
            }
        }

        Log::error('❌ No se pudo procesar el webhook');
        return response()->json(['error' => 'Missing data'], 400);
    }

    public function getOrder($orderId)
    {
        try {
            $accessToken = $this->getAccessToken();

            $response = Http::withToken($accessToken)
                ->get("{$this->paypalBaseUrl}/v2/checkout/orders/{$orderId}");

            return response()->json(array_merge($response->json(), ['sandbox_mode' => true]));
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'sandbox_mode' => true
            ], 500);
        }
    }

    public function checkConfig()
    {
        return response()->json([
            'paypal_base_url' => $this->paypalBaseUrl,
            'client_id_prefix' => substr($this->clientId, 0, 10) . '...',
            'mode' => 'SANDBOX FORZADO',
            'status' => 'Configurado para pruebas'
        ]);
    }

    // Alias para la ruta que apuntaba a debugConfig en routes/api.php
    public function debugConfig()
    {
        return $this->checkConfig();
    }

    // Manejar el retorno público de PayPal (redireccionar al frontend)
    public function handleReturn(Request $request)
    {
        // PayPal devuelve token=ORDER_ID en la query string
        $orderId = $request->query('token');
        $frontendUrl = env('APP_FRONTEND_URL', 'https://mangakaappwebfront-production-b10c.up.railway.app');

        if (!$orderId) {
            Log::warning('PayPal return called without token', $request->all());
            // Si no hay token, redirigir al frontend al cart
            return redirect()->away($frontendUrl . '/cart');
        }

        // Redirigir al frontend a la página que realizará la captura
        return redirect()->away($frontendUrl . '/paypal-return?token=' . urlencode($orderId));
    }
}

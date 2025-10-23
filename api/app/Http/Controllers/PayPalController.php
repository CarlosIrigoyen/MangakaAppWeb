<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\Factura;
use App\Models\DetalleFactura;

class PayPalController extends Controller
{
    private $paypalBaseUrl;
    private $clientId;
    private $clientSecret;

    public function __construct()
    {
        $this->paypalBaseUrl = 'https://api.sandbox.paypal.com';

        $this->clientId = env('PAYPAL_CLIENT_ID');
        $this->clientSecret = env('PAYPAL_CLIENT_SECRET');
    }

    private function getAccessToken()
    {
        try {
            Log::info("🔑 Obteniendo access token de PayPal...");

            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->asForm()
                ->post("{$this->paypalBaseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials'
                ]);

            if ($response->successful()) {
                Log::info("✅ Access token obtenido exitosamente");
                return $response->json()['access_token'];
            }

            Log::error('❌ Error obteniendo access token de PayPal: ' . $response->body());
            throw new \Exception('No se pudo obtener el access token de PayPal: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('❌ Exception en getAccessToken: ' . $e->getMessage());
            throw $e;
        }
    }

    public function createOrder(Request $request)
    {
        Log::info("📥 Recibido request de PayPal desde React: " . json_encode($request->all()));

        // Validación (misma estructura que MercadoPago)
        $request->validate([
            'cliente_id'               => 'required|integer|exists:clientes,id',
            'productos'                => 'required|array|min:1',
            'productos.*.tomo_id'      => 'required|integer|exists:tomos,id',
            'productos.*.titulo'       => 'required|string|max:255',
            'productos.*.cantidad'     => 'required|integer|min:1',
            'productos.*.precio_unitario' => 'required|numeric|min:0',
        ]);

        $clienteId = $request->input('cliente_id');

        // Crear factura (SIN campo paypal_order_id)
        $factura = Factura::create([
            'numero'     => 'FAC-' . time() . '-' . Str::random(6),
            'cliente_id' => $clienteId,
            'pagado'     => false,
        ]);

        // Calcular total y guardar detalles
        $totalAmount = 0;
        $items = [];

        foreach ($request->input('productos', []) as $prod) {
            $subtotal = (float) $prod['cantidad'] * (float) $prod['precio_unitario'];
            $totalAmount += $subtotal;

            // Guardar detalle de factura
            DetalleFactura::create([
                'factura_id'      => $factura->id,
                'tomo_id'         => $prod['tomo_id'],
                'cantidad'        => (int) $prod['cantidad'],
                'precio_unitario' => (float) $prod['precio_unitario'],
                'subtotal'        => $subtotal,
            ]);

            // Preparar items para PayPal
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

        try {
            $accessToken = $this->getAccessToken();

            // Crear orden en PayPal - usando invoice_id como external_reference
            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => 'factura_' . $factura->id,
                        'description' => 'Compra de mangas - Tienda MangakaBaka',
                        'invoice_id' => (string) $factura->id, // ← Usamos invoice_id como external_reference
                        'amount' => [
                            'currency_code' => 'USD',
                            'value' => number_format($totalAmount, 2, '.', ''),
                            'breakdown' => [
                                'item_total' => [
                                    'currency_code' => 'ARS',
                                    'value' => number_format($totalAmount, 2, '.', '')
                                ]
                            ]
                        ],
                        'items' => $items
                    ]
                ],
                'application_context' => [
                    'return_url' => 'https://mangakaappwebfront-production-b10c.up.railway.app/facturas',
                    'cancel_url' => 'https://mangakaappwebfront-production-b10c.up.railway.app/carrito',
                    'brand_name' => 'MangakaBaka Store',
                    'user_action' => 'PAY_NOW',
                    'shipping_preference' => 'NO_SHIPPING'
                ]
            ];

            Log::info("📤 Enviando orden a PayPal: " . json_encode($orderData));

            $response = Http::withToken($accessToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Prefer' => 'return=representation'
                ])
                ->post("{$this->paypalBaseUrl}/v2/checkout/orders", $orderData);

            $responseData = $response->json();

            if (!$response->successful()) {
                Log::error('❌ Error creando orden en PayPal: ' . $response->body());
                $factura->delete();

                return response()->json([
                    'message' => 'Error creando la orden de PayPal.',
                    'paypal_error' => $responseData,
                ], 500);
            }

            Log::info("✅ Orden PayPal creada exitosamente: " . $responseData['id']);

            // NO guardamos paypal_order_id en la factura
            // Usamos invoice_id como referencia

            // Encontrar el link de aprobación
            $approveLink = collect($responseData['links'])->firstWhere('rel', 'approve');

            if (!$approveLink) {
                throw new \Exception('No se encontró el link de aprobación en la respuesta de PayPal');
            }

            return response()->json([
                'id' => $responseData['id'],
                'status' => $responseData['status'],
                'approve_url' => $approveLink['href'],
                'external_reference' => (string) $factura->id, // Devolvemos el ID de la factura
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Exception en createOrder PayPal: ' . $e->getMessage());
            $factura->delete();

            return response()->json([
                'message' => 'Error creando la orden de PayPal.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function captureOrder($orderId)
    {
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
                Log::error('❌ Error capturando orden PayPal: ' . $response->body());
                return response()->json([
                    'message' => 'Error capturando el pago de PayPal.',
                    'paypal_error' => $captureData,
                ], 500);
            }

            Log::info("✅ Orden PayPal capturada: " . json_encode([
                'id' => $captureData['id'],
                'status' => $captureData['status']
            ]));

            // Buscar la factura usando invoice_id desde purchase_units
            $invoiceId = $captureData['purchase_units'][0]['invoice_id'] ?? null;

            if ($invoiceId) {
                $factura = Factura::with('detalles.tomo')->find($invoiceId);

                if ($factura && $captureData['status'] === 'COMPLETED') {
                    // Marcar factura como pagada
                    $factura->update(['pagado' => true]);

                    // Decrementar stock
                    foreach ($factura->detalles as $detalle) {
                        $tomo = $detalle->tomo;
                        if ($tomo) {
                            $tomo->stock = max(0, $tomo->stock - $detalle->cantidad);
                            $tomo->save();
                            Log::info("📦 Stock actualizado - Tomo ID {$tomo->id}: {$tomo->stock} unidades restantes");
                        }
                    }

                    Log::info("✅ Factura {$factura->id} marcada como pagada via PayPal");
                }
            } else {
                Log::warning('⚠️ No se encontró invoice_id en la respuesta de PayPal');
            }

            return response()->json($captureData);

        } catch (\Exception $e) {
            Log::error('❌ Exception en captureOrder PayPal: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error capturando el pago de PayPal.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function webhook(Request $request)
    {
        Log::info('📥 Webhook PayPal recibido:', $request->all());

        $payload = $request->all();
        $eventType = $payload['event_type'] ?? null;
        $resource = $payload['resource'] ?? null;

        Log::info("🔔 Evento PayPal: {$eventType}");

        if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
            $captureId = $resource['id'] ?? null;

            if ($captureId) {
                try {
                    $accessToken = $this->getAccessToken();

                    // Obtener detalles del capture para conseguir el order_id
                    $response = Http::withToken($accessToken)
                        ->get("{$this->paypalBaseUrl}/v2/payments/captures/{$captureId}");

                    if ($response->successful()) {
                        $captureDetails = $response->json();
                        $orderLink = collect($captureDetails['links'])->firstWhere('rel', 'up');

                        if ($orderLink) {
                            // Extraer order_id de la URL
                            $orderUrl = $orderLink['href'];
                            $orderId = basename($orderUrl);

                            // Llamar a captureOrder para procesar el pago
                            return $this->captureOrder($orderId);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('❌ Error procesando webhook PayPal: ' . $e->getMessage());
                }
            }
        }

        return response()->json(['message' => 'Webhook processed'], 200);
    }

    // Método auxiliar para verificar el estado de una orden
    public function getOrder($orderId)
    {
        try {
            $accessToken = $this->getAccessToken();

            $response = Http::withToken($accessToken)
                ->get("{$this->paypalBaseUrl}/v2/checkout/orders/{$orderId}");

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}

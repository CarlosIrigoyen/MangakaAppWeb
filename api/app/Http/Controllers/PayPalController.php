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
        // FORZAR MODO SANDBOX SIEMPRE - incluso en producción
        $this->paypalBaseUrl = 'https://api.sandbox.paypal.com';

        $this->clientId = env('PAYPAL_CLIENT_ID');
        $this->clientSecret = env('PAYPAL_CLIENT_SECRET');

        Log::info("🔧 PayPal Controller configurado en modo SANDBOX forzado");
        Log::info("🔧 Client ID: " . substr($this->clientId, 0, 10) . "...");
        Log::info("🔧 Base URL: " . $this->paypalBaseUrl);
    }

    private function getAccessToken()
    {
        try {
            Log::info("🔑 Obteniendo access token de PayPal Sandbox...");

            $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
                ->asForm()
                ->post("{$this->paypalBaseUrl}/v1/oauth2/token", [
                    'grant_type' => 'client_credentials'
                ]);

            if ($response->successful()) {
                Log::info("✅ Access token de Sandbox obtenido exitosamente");
                return $response->json()['access_token'];
            }

            Log::error('❌ Error obteniendo access token de PayPal Sandbox: ' . $response->body());
            throw new \Exception('No se pudo obtener el access token de PayPal Sandbox: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('❌ Exception en getAccessToken Sandbox: ' . $e->getMessage());
            throw $e;
        }
    }

    public function createOrder(Request $request)
    {
        Log::info("📥 Recibido request de PayPal Sandbox desde React: " . json_encode($request->all()));

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

        // Crear factura
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

            // Crear orden en PayPal Sandbox
            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => 'factura_' . $factura->id,
                        'description' => 'Compra de mangas - Tienda MangakaBaka (SANDBOX)',
                        'invoice_id' => (string) $factura->id,
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
                    'return_url' => 'https://mangakaappwebfront-production-b10c.up.railway.app/facturas',
                    'cancel_url' => 'https://mangakaappwebfront-production-b10c.up.railway.app/carrito',
                    'brand_name' => 'MangakaBaka Store (SANDBOX)',
                    'user_action' => 'PAY_NOW',
                    'shipping_preference' => 'NO_SHIPPING'
                ]
            ];

            Log::info("📤 Enviando orden a PayPal Sandbox: " . json_encode($orderData));

            $response = Http::withToken($accessToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Prefer' => 'return=representation'
                ])
                ->post("{$this->paypalBaseUrl}/v2/checkout/orders", $orderData);

            $responseData = $response->json();

            if (!$response->successful()) {
                Log::error('❌ Error creando orden en PayPal Sandbox: ' . $response->body());
                $factura->delete();

                return response()->json([
                    'message' => 'Error creando la orden de PayPal Sandbox.',
                    'paypal_error' => $responseData,
                    'sandbox_mode' => true
                ], 500);
            }

            Log::info("✅ Orden PayPal Sandbox creada exitosamente: " . $responseData['id']);

            // Encontrar el link de aprobación
            $approveLink = collect($responseData['links'])->firstWhere('rel', 'approve');

            if (!$approveLink) {
                throw new \Exception('No se encontró el link de aprobación en la respuesta de PayPal Sandbox');
            }

            return response()->json([
                'id' => $responseData['id'],
                'status' => $responseData['status'],
                'approve_url' => $approveLink['href'],
                'external_reference' => (string) $factura->id,
                'sandbox_mode' => true,
                'message' => 'MODO PRUEBAS - No se realizará cargo real'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Exception en createOrder PayPal Sandbox: ' . $e->getMessage());
            $factura->delete();

            return response()->json([
                'message' => 'Error creando la orden de PayPal Sandbox.',
                'error' => $e->getMessage(),
                'sandbox_mode' => true
            ], 500);
        }
    }

    public function captureOrder($orderId)
    {
        try {
            $accessToken = $this->getAccessToken();

            Log::info("🎯 Capturando orden PayPal Sandbox: " . $orderId);

            $response = Http::withToken($accessToken)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Prefer' => 'return=representation'
                ])
                ->post("{$this->paypalBaseUrl}/v2/checkout/orders/{$orderId}/capture");

            $captureData = $response->json();

            if (!$response->successful()) {
                Log::error('❌ Error capturando orden PayPal Sandbox: ' . $response->body());
                return response()->json([
                    'message' => 'Error capturando el pago de PayPal Sandbox.',
                    'paypal_error' => $captureData,
                    'sandbox_mode' => true
                ], 500);
            }

            Log::info("✅ Orden PayPal Sandbox capturada: " . json_encode([
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
                            Log::info("📦 Stock actualizado - Tomo ID {$tomo->id}: {$tomo->stock} unidades restantes (SANDBOX)");
                        }
                    }

                    Log::info("✅ Factura {$factura->id} marcada como pagada via PayPal Sandbox");
                }
            } else {
                Log::warning('⚠️ No se encontró invoice_id en la respuesta de PayPal Sandbox');
            }

            return response()->json(array_merge($captureData, ['sandbox_mode' => true]));

        } catch (\Exception $e) {
            Log::error('❌ Exception en captureOrder PayPal Sandbox: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error capturando el pago de PayPal Sandbox.',
                'error' => $e->getMessage(),
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
        // Manejar diferentes tipos de eventos
        switch ($eventType) {
            case 'PAYMENT.CAPTURE.COMPLETED':
                return $this->handlePaymentCompleted($payload);

            case 'PAYMENT.CAPTURE.DENIED':
                Log::info('❌ Pago denegado via webhook');
                return response()->json(['status' => 'denied_processed']);

            case 'PAYMENT.CAPTURE.PENDING':
                Log::info('⏳ Pago pendiente via webhook');
                return response()->json(['status' => 'pending_processed']);

            case 'CHECKOUT.ORDER.APPROVED':
                Log::info('✅ Orden aprobada via webhook');
                $orderId = $payload['resource']['id'] ?? null;
                if ($orderId) {
                    return $this->captureOrder($orderId);
                }
                break;

            default:
                Log::info("🔔 Evento no manejado: {$eventType}");
                break;
        }

        return response()->json(['status' => 'received'], 200);

    } catch (\Exception $e) {
        Log::error('❌ Error en webhook: ' . $e->getMessage());
        return response()->json(['error' => 'Processing failed'], 500);
    }
}

private function handlePaymentCompleted($payload)
{
    $captureId = $payload['resource']['id'] ?? null;
    $orderId = $payload['resource']['supplementary_data']['related_ids']['order_id'] ?? null;
    $invoiceId = $payload['resource']['invoice_id'] ?? null;

    Log::info("💰 Pago completado - Capture: {$captureId}, Order: {$orderId}, Invoice: {$invoiceId}");

    // Si tenemos invoice_id, actualizar directamente
    if ($invoiceId) {
        $factura = Factura::with('detalles.tomo')->find($invoiceId);

        if ($factura && !$factura->pagado) {
            $factura->pagado = true;
            $factura->save();

            foreach ($factura->detalles as $detalle) {
                $tomo = $detalle->tomo;
                if ($tomo) {
                    $stockAnterior = $tomo->stock;
                    $tomo->stock = max(0, $tomo->stock - $detalle->cantidad);
                    $tomo->save();
                    Log::info("📦 Stock actualizado - Tomo ID {$tomo->id}: {$stockAnterior} -> {$tomo->stock}");
                }
            }

            Log::info("✅ Factura {$factura->id} actualizada via webhook");
            return response()->json(['status' => 'success'], 200);
        }
    }

    // Fallback: usar el método captureOrder
    if ($orderId) {
        return $this->captureOrder($orderId);
    }

    Log::error('❌ No se pudo procesar el webhook - sin order_id ni invoice_id');
    return response()->json(['error' => 'Missing data'], 400);
}

    // Método auxiliar para verificar el estado de una orden
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

    // Método para verificar la configuración
    public function checkConfig()
    {
        return response()->json([
            'paypal_base_url' => $this->paypalBaseUrl,
            'client_id_prefix' => substr($this->clientId, 0, 10) . '...',
            'mode' => 'SANDBOX FORZADO',
            'status' => 'Configurado para pruebas'
        ]);
    }
}

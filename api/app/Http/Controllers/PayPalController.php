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

        try {
            // Primero crear la factura usando el FacturaController unificado
            $facturaResponse = app(FacturaController::class)->crearFacturaParaPago(
                new Request(array_merge($request->all(), ['metodo_pago' => 'paypal']))
            );

            if ($facturaResponse->getStatusCode() !== 201) {
                return $facturaResponse;
            }

            $facturaData = json_decode($facturaResponse->getContent(), true);
            $facturaId = $facturaData['factura_id'];
            $externalReference = $facturaData['external_reference'];
            $totalAmount = $facturaData['total'];

            Log::info("✅ Factura creada para PayPal: {$externalReference}, Total: {$totalAmount} ARS");

            $accessToken = $this->getAccessToken();

            // Crear orden en PayPal Sandbox - AHORA EN PESOS ARGENTINOS
            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => $externalReference, // Usar external_reference de la factura
                        'description' => 'Compra de mangas - Tienda MangakaBaka (SANDBOX)',
                        'invoice_id' => (string) $facturaId,
                        'amount' => [
                            'currency_code' => 'ARS', // CAMBIADO DE USD A ARS
                            'value' => number_format($totalAmount, 2, '.', ''),
                            'breakdown' => [
                                'item_total' => [
                                    'currency_code' => 'ARS', // CAMBIADO DE USD A ARS
                                    'value' => number_format($totalAmount, 2, '.', '')
                                ]
                            ]
                        ],
                        'items' => $this->prepareItems($request->input('productos', []))
                    ]
                ],
                'application_context' => [
                    'return_url' => 'https://mangakaappwebfront-production-b10c.up.railway.app/facturas?paypal_success=true&external_reference=' . $externalReference,
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

                // Si falla, eliminar la factura creada
                Factura::where('external_reference', $externalReference)->delete();

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

            // Guardar el order_id de PayPal en la sesión para usarlo después
            session(['paypal_order_' . $externalReference => $responseData['id']]);

            return response()->json([
                'id' => $responseData['id'],
                'status' => $responseData['status'],
                'approve_url' => $approveLink['href'],
                'external_reference' => $externalReference,
                'sandbox_mode' => true,
                'message' => 'MODO PRUEBAS - No se realizará cargo real'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Exception en createOrder PayPal Sandbox: ' . $e->getMessage());

            // Si hay error, eliminar cualquier factura creada
            if (isset($externalReference)) {
                Factura::where('external_reference', $externalReference)->delete();
            }

            return response()->json([
                'message' => 'Error creando la orden de PayPal Sandbox.',
                'error' => $e->getMessage(),
                'sandbox_mode' => true
            ], 500);
        }
    }

    /**
     * Preparar items para PayPal en formato ARS
     */
    private function prepareItems($productos)
    {
        return array_map(function($prod) {
            return [
                'name' => substr($prod['titulo'], 0, 127),
                'quantity' => (string) $prod['cantidad'],
                'unit_amount' => [
                    'currency_code' => 'ARS', // CAMBIADO DE USD A ARS
                    'value' => number_format($prod['precio_unitario'], 2, '.', '')
                ],
                'category' => 'DIGITAL_GOODS'
            ];
        }, $productos);
    }

    public function captureOrder($externalReference)
    {
        try {
            // Obtener el order_id de PayPal desde la sesión
            $orderId = session('paypal_order_' . $externalReference);

            if (!$orderId) {
                Log::error("❌ No se encontró order_id para la referencia: {$externalReference}");
                return response()->json([
                    'message' => 'No se encontró la orden de PayPal.',
                    'sandbox_mode' => true
                ], 400);
            }

            $accessToken = $this->getAccessToken();

            Log::info("🎯 Capturando orden PayPal Sandbox: {$orderId} para referencia: {$externalReference}");

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

            // Buscar la factura por external_reference
            $factura = Factura::where('external_reference', $externalReference)->first();

            if ($factura && $captureData['status'] === 'COMPLETED' && !$factura->pagado) {
                // Marcar factura como pagada usando el FacturaController unificado
                $markPaidResponse = app(FacturaController::class)->marcarComoPagada(
                    new Request([
                        'payment_id' => $orderId,
                        'fecha_pago' => now()
                    ]),
                    $factura->id
                );

                if ($markPaidResponse->getStatusCode() === 200) {
                    Log::info("💰 Factura {$factura->id} marcada como pagada via PayPal Sandbox");

                    // Limpiar la sesión
                    session()->forget('paypal_order_' . $externalReference);
                }
            }

            return response()->json(array_merge($captureData, [
                'sandbox_mode' => true,
                'factura_id' => $factura->id ?? null,
                'external_reference' => $externalReference
            ]));

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
                        // Buscar external_reference en los datos del recurso
                        $externalReference = $payload['resource']['purchase_units'][0]['reference_id'] ?? null;
                        if ($externalReference) {
                            return $this->captureOrder($externalReference);
                        }
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
        $externalReference = $payload['resource']['custom_id'] ?? null;

        Log::info("💰 Pago completado - Capture: {$captureId}, Order: {$orderId}, Invoice: {$invoiceId}, Reference: {$externalReference}");

        // Si tenemos external_reference, procesar directamente
        if ($externalReference) {
            $factura = Factura::where('external_reference', $externalReference)->first();

            if ($factura && !$factura->pagado) {
                // Marcar como pagada usando el FacturaController unificado
                $markPaidResponse = app(FacturaController::class)->marcarComoPagada(
                    new Request([
                        'payment_id' => $captureId,
                        'fecha_pago' => now()
                    ]),
                    $factura->id
                );

                if ($markPaidResponse->getStatusCode() === 200) {
                    Log::info("✅ Factura {$factura->id} actualizada via webhook");
                    return response()->json(['status' => 'success'], 200);
                }
            }
        }

        // Fallback: usar invoice_id
        if ($invoiceId) {
            $factura = Factura::find($invoiceId);
            if ($factura && !$factura->pagado) {
                $markPaidResponse = app(FacturaController::class)->marcarComoPagada(
                    new Request([
                        'payment_id' => $captureId,
                        'fecha_pago' => now()
                    ]),
                    $factura->id
                );

                if ($markPaidResponse->getStatusCode() === 200) {
                    Log::info("✅ Factura {$factura->id} actualizada via webhook (fallback invoice_id)");
                    return response()->json(['status' => 'success'], 200);
                }
            }
        }

        Log::error('❌ No se pudo procesar el webhook - sin external_reference ni invoice_id válidos');
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
            'currency' => 'ARS', // Ahora usando pesos argentinos
            'status' => 'Configurado para pruebas en ARS'
        ]);
    }
}

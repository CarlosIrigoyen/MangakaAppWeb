<?php
// app/Http/Controllers/PayPalController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Factura;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PayPalController extends Controller
{
    public function createOrder(Request $request)
    {
        Log::info("📥 Recibido request de React para PayPal: " . json_encode($request->all()));

        try {
            // Usar el FacturaController para crear la factura
            $facturaResponse = app(FacturaController::class)->crearFacturaParaPago(
                new Request(array_merge($request->all(), ['metodo_pago' => 'paypal']))
            );

            if ($facturaResponse->getStatusCode() !== 201) {
                return $facturaResponse;
            }

            $facturaData = json_decode($facturaResponse->getContent(), true);
            $externalReference = $facturaData['external_reference'];
            $total = $facturaData['total'];

            Log::info("✅ Factura creada para PayPal: {$externalReference}");

            $provider = new PayPalClient;
            $provider->setApiCredentials(config('paypal'));
            $provider->getAccessToken();

            $order = $provider->createOrder([
                "intent" => "CAPTURE",
                "purchase_units" => [
                    [
                        "reference_id" => $externalReference,
                        "amount" => [
                            "currency_code" => "USD",
                            "value" => number_format($total / 100, 2), // Convertir a USD si es necesario
                            "breakdown" => [
                                "item_total" => [
                                    "currency_code" => "USD",
                                    "value" => number_format($total / 100, 2)
                                ]
                            ]
                        ],
                        "items" => array_map(function($product) {
                            return [
                                "name" => $product['titulo'],
                                "quantity" => $product['cantidad'],
                                "unit_amount" => [
                                    "currency_code" => "USD",
                                    "value" => number_format($product['precio_unitario'] / 100, 2)
                                ]
                            ];
                        }, $request->input('productos'))
                    ]
                ],
                "application_context" => [
                    "return_url" => "https://mangakaappwebfront-production-b10c.up.railway.app/facturas?paypal_success=true&external_reference=" . $externalReference,
                    "cancel_url" => "https://mangakaappwebfront-production-b10c.up.railway.app/checkout/failure",
                    "brand_name" => "Mangaka Baka Shop"
                ]
            ]);

            if (isset($order['id'])) {
                // Guardar order_id temporalmente
                session(['paypal_order_' . $externalReference => $order['id']]);

                return response()->json([
                    'approve_url' => collect($order['links'])->where('rel', 'approve')->first()['href'],
                    'order_id' => $order['id'],
                    'external_reference' => $externalReference
                ]);
            } else {
                // Si falla, eliminar la factura creada
                Factura::where('external_reference', $externalReference)->delete();

                Log::error('❌ Error creando orden PayPal: ' . json_encode($order));
                return response()->json([
                    'message' => 'Error creando la orden de PayPal.',
                    'paypal_error' => $order
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('❌ Error en createOrder PayPal: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error creando la orden de PayPal.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function captureOrder(Request $request, $orderId)
    {
        Log::info("🎯 Capturando orden PayPal: {$orderId}");

        try {
            $provider = new PayPalClient;
            $provider->setApiCredentials(config('paypal'));
            $provider->getAccessToken();

            $result = $provider->capturePaymentOrder($orderId);

            if ($result['status'] === 'COMPLETED') {
                $externalReference = $result['purchase_units'][0]['reference_id'] ?? null;

                if ($externalReference) {
                    $factura = Factura::where('external_reference', $externalReference)->first();

                    if ($factura && !$factura->pagado) {
                        // Marcar como pagada usando el FacturaController
                        $markPaidResponse = app(FacturaController::class)->marcarComoPagada(
                            new Request([
                                'payment_id' => $orderId,
                                'fecha_pago' => now()
                            ]),
                            $factura->id
                        );

                        if ($markPaidResponse->getStatusCode() === 200) {
                            Log::info("💰 Factura {$factura->id} marcada como pagada via PayPal");
                        }
                    }
                }

                return response()->json([
                    'message' => 'Pago completado exitosamente',
                    'factura_id' => $factura->id ?? null,
                    'external_reference' => $externalReference
                ]);
            }

            return response()->json([
                'message' => 'El pago no pudo ser capturado',
                'paypal_status' => $result['status']
            ], 400);

        } catch (\Exception $e) {
            Log::error('❌ Error capturando orden PayPal: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error capturando el pago de PayPal.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function webhook(Request $request)
    {
        Log::info('📥 Webhook PayPal recibido:', $request->all());
        // Implementar lógica de webhook para PayPal si es necesario
        return response()->json(['message' => 'Webhook recibido'], 200);
    }
}

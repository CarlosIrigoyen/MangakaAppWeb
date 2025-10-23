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

            throw new \Exception('No se pudo obtener el access token: ' . $response->body());

        } catch (\Exception $e) {
            Log::error('Error en getAccessToken: ' . $e->getMessage());
            throw $e;
        }
    }

    public function createOrder(Request $request)
    {
        Log::info("📥 createOrder PayPal: ", $request->all());

        DB::beginTransaction();
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

            // 1. Verificar stock
            foreach ($productos as $prod) {
                $tomo = Tomo::find($prod['tomo_id']);
                if (!$tomo || $tomo->stock < $prod['cantidad']) {
                    throw new \Exception("Stock insuficiente para el tomo ID {$prod['tomo_id']}");
                }
            }

            // 2. Crear factura
            $factura = Factura::create([
                'numero' => 'PP-' . time() . '-' . Str::random(6),
                'cliente_id' => $clienteId,
                'pagado' => false,
            ]);

            // 3. Crear detalles de factura
            $totalAmount = 0;
            $items = [];

            foreach ($productos as $prod) {
                $subtotal = (float) $prod['cantidad'] * $prod['precio_unitario'];
                $totalAmount += $subtotal;

                DetalleFactura::create([
                    'factura_id' => $factura->id,
                    'tomo_id' => $prod['tomo_id'],
                    'cantidad' => (int) $prod['cantidad'],
                    'precio_unitario' => (float) $prod['precio_unitario'],
                    'subtotal' => $subtotal,
                ]);

                $items[] = [
                    'name' => substr($prod['titulo'], 0, 127),
                    'quantity' => (string) $prod['cantidad'],
                    'unit_amount' => [
                        'currency_code' => 'ARS',
                        'value' => number_format($prod['precio_unitario'], 2, '.', '')
                    ],
                    'category' => 'DIGITAL_GOODS'
                ];
            }

            // 4. Crear orden en PayPal
            $accessToken = $this->getAccessToken();

            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => 'factura_' . $factura->id,
                        'description' => 'Compra de mangas - MangakaBaka',
                        'invoice_id' => (string) $factura->id,
                        'amount' => [
                            'currency_code' => 'ARS',
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
                    'return_url' => 'https://mangakaappwebfront-production-b10c.up.railway.app/facturas?paypal_success=true&factura_id=' . $factura->id,
                    'cancel_url' => 'https://mangakaappwebfront-production-b10c.up.railway.app/carrito',
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
                throw new \Exception('Error PayPal: ' . $response->body());
            }

            $approveLink = collect($responseData['links'])->firstWhere('rel', 'approve');
            if (!$approveLink) {
                throw new \Exception('No se encontró el link de aprobación');
            }

            DB::commit();

            Log::info("✅ Orden creada - Factura: {$factura->id}, PayPal: {$responseData['id']}");

            return response()->json([
                'id' => $responseData['id'],
                'status' => $responseData['status'],
                'approve_url' => $approveLink['href'],
                'factura_id' => (string) $factura->id,
                'sandbox_mode' => true,
                'message' => 'MODO PRUEBAS - No se realizará cargo real'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
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
                throw new \Exception('Error capturando orden: ' . $response->body());
            }

            Log::info("✅ Orden PayPal capturada: " . json_encode([
                'id' => $captureData['id'],
                'status' => $captureData['status']
            ]));

            // Buscar la factura usando invoice_id
            $invoiceId = $captureData['purchase_units'][0]['invoice_id'] ?? null;

            if (!$invoiceId) {
                throw new \Exception('No se encontró invoice_id en la respuesta');
            }

            $factura = Factura::with('detalles.tomo')->find($invoiceId);

            if (!$factura) {
                throw new \Exception("No se encontró factura con ID: {$invoiceId}");
            }

            if ($captureData['status'] === 'COMPLETED' && !$factura->pagado) {
                // Marcar factura como pagada
                $factura->pagado = true;
                $factura->save();

                Log::info("💰 Factura {$factura->id} marcada como pagada");

                // Decrementar stock
                foreach ($factura->detalles as $detalle) {
                    $tomo = $detalle->tomo;
                    if ($tomo) {
                        $stockAnterior = $tomo->stock;
                        $tomo->decrement('stock', $detalle->cantidad);
                        Log::info("📦 Stock actualizado - Tomo {$tomo->id}: {$stockAnterior} -> {$tomo->stock}");
                    }
                }

                Log::info("✅ Stock decrementado para factura {$factura->id}");
            } else {
                Log::info("ℹ️ Factura {$factura->id} ya estaba pagada o estado no completado");
            }

            DB::commit();

            return response()->json(array_merge($captureData, [
                'sandbox_mode' => true,
                'factura_id' => $factura->id,
                'factura_pagada' => $factura->pagado
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

    /**
     * Nuevo método para procesar el retorno de PayPal
     */
    public function handleReturn(Request $request)
    {
        Log::info("🔄 Handle Return PayPal: ", $request->all());

        $token = $request->query('token');
        $facturaId = $request->query('factura_id');

        if (!$token) {
            Log::error('❌ No se encontró token en el return URL');
            return response()->json(['message' => 'Token no proporcionado'], 400);
        }

        try {
            // Capturar la orden usando el token (orderId)
            $captureResponse = $this->captureOrder($token);
            $captureData = json_decode($captureResponse->getContent(), true);

            if ($captureResponse->getStatusCode() === 200) {
                Log::info("✅ Pago procesado exitosamente para factura: {$facturaId}");
                return response()->json([
                    'message' => 'Pago procesado exitosamente',
                    'factura_id' => $facturaId,
                    'paypal_status' => $captureData['status'] ?? 'unknown'
                ]);
            } else {
                throw new \Exception('Error en captureOrder: ' . ($captureData['message'] ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            Log::error('❌ Error en handleReturn: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error procesando el pago: ' . $e->getMessage()
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

                case 'CHECKOUT.ORDER.APPROVED':
                    $orderId = $payload['resource']['id'] ?? null;
                    if ($orderId) {
                        Log::info("✅ Orden aprobada via webhook: {$orderId}");
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

        if ($invoiceId) {
            $factura = Factura::with('detalles.tomo')->find($invoiceId);

            if ($factura && !$factura->pagado) {
                DB::beginTransaction();
                try {
                    $factura->pagado = true;
                    $factura->save();

                    foreach ($factura->detalles as $detalle) {
                        $tomo = $detalle->tomo;
                        if ($tomo) {
                            $stockAnterior = $tomo->stock;
                            $tomo->decrement('stock', $detalle->cantidad);
                            Log::info("📦 Stock actualizado via webhook - Tomo {$tomo->id}: {$stockAnterior} -> {$tomo->stock}");
                        }
                    }

                    DB::commit();
                    Log::info("✅ Factura {$factura->id} actualizada via webhook");
                    return response()->json(['status' => 'success'], 200);

                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('❌ Error en handlePaymentCompleted: ' . $e->getMessage());
                    return response()->json(['error' => 'Processing failed'], 500);
                }
            }
        }

        Log::error('❌ No se pudo procesar el webhook - sin invoice_id válido');
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
}

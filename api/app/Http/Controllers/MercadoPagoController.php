<?php
// app/Http/Controllers/MercadoPagoController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;
use MercadoPago\Client\Payment\PaymentClient;
use App\Models\Factura;
use App\Models\DetalleFactura;

class MercadoPagoController extends Controller
{
    public function __construct()
    {
        MercadoPagoConfig::setAccessToken(env('MP_ACCESS_TOKEN'));
    }

    public function createPreference(Request $request)
    {
        Log::info("📥 Recibido request de React para MercadoPago: " . json_encode($request->all()));

        try {
            // Usar el FacturaController para crear la factura
            $facturaResponse = app(FacturaController::class)->crearFacturaParaPago(
                new Request(array_merge($request->all(), ['metodo_pago' => 'mercadopago']))
            );

            if ($facturaResponse->getStatusCode() !== 201) {
                return $facturaResponse;
            }

            $facturaData = json_decode($facturaResponse->getContent(), true);
            $externalReference = $facturaData['external_reference'];
            $total = $facturaData['total'];

            Log::info("✅ Factura creada para MercadoPago: {$externalReference}");

            // Configurar preferencia
            $preferenceData = [
                'items' => [
                    [
                        'title' => 'Compra de Mangas - Mangaka Baka Shop',
                        'quantity' => 1,
                        'unit_price' => (float) $total,
                        'currency_id' => 'ARS',
                    ]
                ],
                'back_urls' => [
                    'success' => 'https://mangakaappwebfront-production-b10c.up.railway.app/facturas',
                    'failure' => 'https://mangakaappwebfront-production-b10c.up.railway.app/checkout/failure',
                    'pending' => 'https://mangakaappwebfront-production-b10c.up.railway.app/checkout/pending',
                ],
                'auto_return' => 'approved',
                'notification_url' => 'https://mangakaappweb-production.up.railway.app/api/mercadopago/webhook',
                'external_reference' => $externalReference,
            ];

            $preference = (new PreferenceClient())->create($preferenceData);

            // Guardar información en session temporal
            session(['mp_pending_' . $externalReference => [
                'factura_id' => $facturaData['factura_id'],
                'cliente_id' => $request->input('cliente_id'),
                'productos' => $request->input('productos')
            ]]);

            return response()->json([
                'init_point' => $preference->init_point,
                'id' => $preference->id,
                'external_reference' => $externalReference,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error en createPreference MercadoPago: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error creando la preferencia de pago.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function webhook(Request $request)
    {
        Log::info('📥 Webhook MercadoPago recibido:', $request->all());

        $payload = $request->all();
        $type = $payload['type'] ?? null;
        $id = $payload['data']['id'] ?? $payload['data_id'] ?? null;

        if ($type !== 'payment' || !$id) {
            Log::warning('⚠️ Webhook MercadoPago sin datos válidos');
            return response()->json(['message' => 'Datos incompletos'], 400);
        }

        try {
            $paymentClient = new PaymentClient();
            $payment = $paymentClient->get($id);

            Log::info('✅ Información del pago MercadoPago:', [
                'id' => $payment->id,
                'status' => $payment->status,
                'external_reference' => $payment->external_reference,
            ]);

            if ($payment->status === 'approved') {
                $externalReference = $payment->external_reference;

                // Buscar la factura por external_reference
                $factura = Factura::where('external_reference', $externalReference)->first();

                if ($factura && !$factura->pagado) {
                    // Marcar como pagada usando el FacturaController
                    $markPaidResponse = app(FacturaController::class)->marcarComoPagada(
                        new Request(['payment_id' => $payment->id]),
                        $factura->id
                    );

                    if ($markPaidResponse->getStatusCode() === 200) {
                        Log::info("💰 Factura {$factura->id} marcada como pagada via MercadoPago");
                    }
                }
            }

        } catch (MPApiException $e) {
            Log::error("❌ Error al obtener el pago desde MercadoPago SDK: " . $e->getMessage());
            return response()->json(['message' => 'Error al procesar el pago'], 500);
        }

        return response()->json(['message' => 'Webhook procesado'], 200);
    }
}

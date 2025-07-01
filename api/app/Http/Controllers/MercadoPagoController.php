<?php

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

   public function createPreference(Request $request)
{
    MercadoPagoConfig::setAccessToken(env('MERCADO_PAGO_ACCESS_TOKEN'));

    $preferenceClient = new PreferenceClient();

    $preferenceData = [
        'items' => [[
            'title' => 'Producto de prueba',
            'quantity' => 1,
            'unit_price' => 1000,
            'currency_id' => 'ARS',
        ]],
        'back_urls' => [
            'success' => env('APP_FRONT_URL') . '/success',
            'failure' => env('APP_FRONT_URL') . '/failure',
            'pending' => env('APP_FRONT_URL') . '/pending',
        ],
        'auto_return' => 'approved',
        'notification_url' => env('APP_API_URL') . '/mercadopago/webhook',
    ];

    $preference = $preferenceClient->create($preferenceData);

    return response()->json([
        'init_point' => $preference->init_point,
    ]);
}

    public function webhook(Request $request)
    {
        Log::info('📥 Webhook recibido:', $request->all());

        $payload = $request->all();
        $type    = $payload['type']       ?? null;
        $id      = $payload['data']['id'] ?? $payload['data_id'] ?? null;

        if ($type !== 'payment' || ! $id) {
            Log::warning('⚠️ Webhook sin datos válidos (tipo o ID ausente)');
            return response()->json(['message' => 'Datos incompletos'], 400);
        }

        try {
            $paymentClient = new PaymentClient();
            $payment       = $paymentClient->get($id);

            Log::info('✅ Información del pago obtenida desde SDK:', [
                'id'                 => $payment->id,
                'status'             => $payment->status,
                'status_detail'      => $payment->status_detail,
                'external_reference' => $payment->external_reference,
                'transaction_amount' => $payment->transaction_amount,
            ]);

            if ($payment->status === 'approved') {
                $factura = Factura::with('detalles.tomo')
                                  ->find($payment->external_reference);

                if ($factura) {
                    $factura->pagado = true;
                    $factura->save();
                    Log::info("✅ Factura #{$factura->id} marcada como pagada");

                    foreach ($factura->detalles as $detalle) {
                        $tomo = $detalle->tomo;
                        if ($tomo) {
                            $originalStock = $tomo->stock ?? 0;
                            $decrement     = (int) $detalle->cantidad;
                            $tomo->stock   = max(0, $originalStock - $decrement);
                            $tomo->save();
                            Log::info("📦 Tomo ID {$tomo->id} stock: {$originalStock} → {$tomo->stock}");
                        } else {
                            Log::warning("⚠️ DetalleFactura #{$detalle->id} sin tomo asociado");
                        }
                    }
                } else {
                    Log::warning("⚠️ Factura no encontrada: {$payment->external_reference}");
                }
            }
        } catch (MPApiException $e) {
            $error = $e->getApiResponse()
                ? $e->getApiResponse()->getContent()
                : $e->getMessage();
            Log::error("❌ Error al obtener el pago desde SDK:", ['message' => $error]);
            return response()->json(['message' => 'Error al obtener el pago'], 500);
        }

        return response()->json(['message' => 'Pago registrado en log'], 200);
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;                  // <<— IMPORTANTE: antes de la clase
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
        Log::info("📥 Recibido request de react: " . json_encode($request->all()));
        $cliente = $request->user();

        $factura = Factura::create([
            'numero'     => Str::uuid()->toString(),
            'cliente_id' => $cliente->id,
            'pagado'     => false,
        ]);

        $items = [];
        foreach ($request->input('productos', []) as $prod) {
            $items[] = [
                'title'       => $prod['titulo'],
                'quantity'    => (int) $prod['cantidad'],
                'unit_price'  => (float) $prod['precio_unitario'],
                'currency_id' => 'ARS',
            ];

            DetalleFactura::create([
                'factura_id'      => $factura->id,
                'tomo_id'         => $prod['tomo_id'],
                'cantidad'        => (int) $prod['cantidad'],
                'precio_unitario' => (float) $prod['precio_unitario'],
                'subtotal'        => (float) $prod['cantidad'] * $prod['precio_unitario'],
            ]);
        }

        $preferenceData = [
            'items' => $items,
            'back_urls' => [
                 'success' => 'https://mangakaappwebfront-production.up.railway.app',
                 'failure' => 'https://mangakaapp.loca.lt/checkout/failure',
                 'pending' => 'https://mangakaapp.loca.lt/checkout/pending',
            ],
            'auto_return'      => 'approved',
            // Notification URL hardcodeada al túnel de Ngrok
            'notification_url'   =>'https://mangakaappweb-production.up.railway.app/api/mercadopago/webhook',
            'external_reference' => (string) $factura->id,
        ];

        try {
            $preference = (new PreferenceClient())->create($preferenceData);

            return response()->json([
                'init_point'         => $preference->init_point,
                'id'                 => $preference->id,
                'external_reference' => $preference->external_reference,
            ]);
        } catch (MPApiException $e) {
            $factura->delete();

            $errorContent = $e->getApiResponse()
                ? $e->getApiResponse()->getContent()
                : $e->getMessage();

            return response()->json([
                'message' => 'Error creando la preferencia de pago.',
                'errors'  => $errorContent,
            ], 500);
        }
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
            // 1) Marcar factura como pagada
            $factura = Factura::with('detalles.tomo')
                              ->find($payment->external_reference);

            if (! $factura) {
                Log::warning("⚠️ Factura no encontrada: {$payment->external_reference}");
            } else {
                $factura->pagado = true;
                $factura->save();
                Log::info("✅ Factura #{$factura->id} marcada como pagada");

                // 2) Para cada detalle, decrementar stock del tomo
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
            }
        }
    } catch (MPApiException $e) {
        $error = $e->getApiResponse()
            ? $e->getApiResponse()->getContent()
            : $e->getMessage();

        Log::error("❌ Error al obtener el pago desde SDK:", [
            'message' => $error,
        ]);

        return response()->json(['message' => 'Error al obtener el pago'], 500);
    }

    return response()->json(['message' => 'Pago registrado en log'], 200);
}

}

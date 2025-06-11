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
                'success' => 'http://localhost:3000/checkout/success',
                'failure' => 'http://localhost:3000/checkout/failure',
                'pending' => 'http://localhost:3000/checkout/pending',
            ],
            'external_reference' => (string) $factura->id,
        ];

        try {
            $preference = (new PreferenceClient())->create($preferenceData);

            return response()->json([
                'sandbox_init_point' => $preference->sandbox_init_point,
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

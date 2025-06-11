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
    Log::info('📥 MercadoPago Webhook recibido: ', $request->all());

    $payload = $request->all();
    $type    = $payload['type']       ?? null;
    $id      = $payload['data']['id'] ?? $payload['data_id'] ?? null;

    if ($type !== 'payment' || ! $id) {
        return response()->json(['message' => 'Webhook sin datos de pago'], 400);
    }

    Log::info("🕵️‍♂️ Buscando pago con ID: {$id}");

    $url   = "https://api.mercadopago.com/v1/payments/{$id}";
    $token = env('MP_ACCESS_TOKEN');

    // Intentos en caso de 404
    $maxAttempts = 10;
    $attempt     = 0;
    $response    = null;

    while ($attempt < $maxAttempts) {
        $response = Http::withToken($token)->get($url);

        if ($response->ok()) {
            break;
        }

        if ($response->status() === 404) {
            $attempt++;
            Log::warning("⚠️ Pago no encontrado (404). Intento {$attempt}/{$maxAttempts}, reintentando en 1s...");
            sleep(10);
        } else {
            // otro error distinto a 404 → salimos
            break;
        }
    }

    if (! $response->ok()) {
        Log::error("❌ Error HTTP ({$response->status()}) al consultar el pago tras {$attempt} reintentos: {$response->body()}");
        return response()->json([
            'message' => 'Error al obtener el pago',
            'status'  => $response->status(),
            'body'    => $response->body(),
        ], 500);
    }

    $payment = $response->json();
    Log::info('💵 Pago recuperado:', [
        'status'             => $payment['status']             ?? null,
        'status_detail'      => $payment['status_detail']      ?? null,
        'external_reference' => $payment['external_reference'] ?? null,
        'transaction_amount' => $payment['transaction_amount'] ?? null,
    ]);

    if (($payment['status'] ?? '') === 'approved') {
        $factura = Factura::find($payment['external_reference']);
        if ($factura) {
            $factura->pagado = true;
            $factura->save();
            Log::info("✅ Factura #{$factura->id} marcada como pagada");
        } else {
            Log::warning("⚠️ No se encontró factura con ID: {$payment['external_reference']}");
        }
    }

    return response()->json(['message' => 'Pago procesado con éxito'], 200);
}

}

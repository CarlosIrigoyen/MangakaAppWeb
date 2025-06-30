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
    public function __construct()
    {
        MercadoPagoConfig::setAccessToken(env('MP_ACCESS_TOKEN'));
    }

    public function createPreference(Request $request)
    {
        Log::info("📥 Recibido request de React (productos): " . json_encode($request->all()));

        // Validamos solo los productos, el cliente siempre es 1 en pruebas
        $request->validate([
            'productos'                  => 'required|array|min:1',
            'productos.*.tomo_id'        => 'required|integer|exists:tomos,id',
            'productos.*.titulo'         => 'required|string',
            'productos.*.cantidad'       => 'required|integer|min:1',
            'productos.*.precio_unitario'=> 'required|numeric|min:0',
        ]);

        // Para pruebas: cliente fijo
        $clienteId = 1;

        // Creamos la factura en estado no pagado
        $factura = Factura::create([
            'numero'     => Str::uuid()->toString(),
            'cliente_id' => $clienteId,
            'pagado'     => false,
        ]);

        // Preparamos los items y guardamos detalles
        $items = [];
        foreach ($request->input('productos') as $prod) {
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

        // Configuramos la preferencia de pago
        $preferenceData = [
            'items' => $items,
            'back_urls' => [
                'success' => env('APP_FRONT_URL').'/facturas',
                'failure' => env('APP_FRONT_URL').'/checkout/failure',
                'pending' => env('APP_FRONT_URL').'/checkout/pending',
            ],
            'auto_return'        => 'approved',
            'notification_url'   => env('APP_API_URL').'/mercadopago/webhook',
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
            // Si falla, eliminamos la factura de prueba
            $factura->delete();
            $errorContent = method_exists($e, 'getApiResponse') && $e->getApiResponse()
                ? $e->getApiResponse()->getContent()
                : $e->getMessage();
            Log::error('[MP] Error creando preference: ' . json_encode($errorContent));

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

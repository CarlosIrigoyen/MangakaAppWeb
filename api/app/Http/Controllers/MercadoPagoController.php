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
        Log::info('▶▶ Llego a createPreference, payload:', $request->all());

        // Validación del array de productos
        $request->validate([
            'productos'                   => 'required|array|min:1',
            'productos.*.tomo_id'         => 'required|integer|exists:tomos,id',
            'productos.*.titulo'          => 'required|string',
            'productos.*.cantidad'        => 'required|integer|min:1',
            'productos.*.precio_unitario' => 'required|numeric|min:0',
        ]);

        $clienteId = 1;
        Log::info("▶▶ Usando cliente_id fijo: {$clienteId}");

        // Crea factura
        $factura = Factura::create([
            'numero'     => Str::uuid(),
            'cliente_id' => $clienteId,
            'pagado'     => false,
        ]);
        Log::info("▶▶ Factura creada ID {$factura->id}");

        // Prepara items y detalles
        $items = [];
        foreach ($request->productos as $p) {
            $items[] = [
                'title'       => $p['titulo'],
                'quantity'    => (int) $p['cantidad'],
                'unit_price'  => (float) $p['precio_unitario'],
                'currency_id' => 'ARS',
            ];
            DetalleFactura::create([
                'factura_id'      => $factura->id,
                'tomo_id'         => $p['tomo_id'],
                'cantidad'        => $p['cantidad'],
                'precio_unitario' => $p['precio_unitario'],
                'subtotal'        => $p['cantidad'] * $p['precio_unitario'],
            ]);
        }

        $preferenceData = [
            'items'               => $items,
            'back_urls'           => [
                'success' => 'https://www.google.com',
                'failure' => 'https://www.google.com',
                'pending' => 'https://www.google.com',
            ],
            'auto_return'         => 'approved',
            'notification_url'    => env('APP_API_URL').'/mercadopago/webhook',
            'external_reference'  => (string) $factura->id,
        ];
        Log::debug('▶▶ preferenceData:', $preferenceData);

        try {
            $preference = (new PreferenceClient())->create($preferenceData);
            Log::info('✅ Preference creada, init_point: '.$preference->init_point);

            return response()->json([
                'init_point'         => $preference->init_point,
                'id'                 => $preference->id,
                'external_reference' => $preference->external_reference,
            ]);
        } catch (MPApiException $e) {
            // Registro completo de la excepción
            Log::error('[MP] Exception creando preference: '.$e->__toString());
            $factura->delete();

            return response()->json([
                'message' => 'Error creando la preferencia de pago.',
                'errors'  => $e->getMessage(),
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

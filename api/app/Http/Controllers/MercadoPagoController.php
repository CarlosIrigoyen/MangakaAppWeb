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
        // Configura el Access Token de MercadoPago
        MercadoPagoConfig::setAccessToken(env('MP_ACCESS_TOKEN'));
    }

    public function createPreference(Request $request)
    {
        // Logueo del request para debugging
        Log::info("📥 Recibido request de React: " . json_encode($request->all()));

        // Validación incluyendo cliente_id
        $request->validate([
            'cliente_id'               => 'required|integer|exists:clientes,id',
            'productos'                => 'required|array|min:1',
            'productos.*.tomo_id'      => 'required|integer|exists:tomos,id',
            'productos.*.titulo'       => 'required|string',
            'productos.*.cantidad'     => 'required|integer|min:1',
            'productos.*.precio_unitario' => 'required|numeric|min:0',
        ]);

        // Obtenemos el cliente_id enviado desde el frontend
        $clienteId = $request->input('cliente_id');

        // Creamos la factura principal
        $factura = Factura::create([
            'numero'     => Str::uuid()->toString(),
            'cliente_id' => $clienteId,
            'pagado'     => false,
        ]);

        // Armamos los ítems para MercadoPago y guardamos detalles de factura
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

        // Configuramos los datos de la preferencia de pago
        $preferenceData = [
            'items'              => $items,
            'back_urls'          => [
                'success' => 'https://mangakaappwebfront-production-b10c.up.railway.app/facturas',
                'failure' => 'https://mangakaappwebfront-production-b10c.up.railway.app/checkout/failure',
                'pending' => 'https://mangakaappwebfront-production-b10c.up.railway.app/checkout/pending',
            ],
            'auto_return'        => 'approved',
            'notification_url'   => 'https://mangakaappweb-production.up.railway.app/api/mercadopago/webhook',
            'external_reference' => (string) $factura->id,
        ];

        try {
            // Creamos la preferencia en MercadoPago
            $preference = (new PreferenceClient())->create($preferenceData);

            // Devolvemos al frontend los datos necesarios para redireccionar
            return response()->json([
                'init_point'         => $preference->init_point,
                'id'                 => $preference->id,
                'external_reference' => $preference->external_reference,
            ]);

        } catch (MPApiException $e) {
            // Si falla, eliminamos la factura y devolvemos el error
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
                // Marcar factura como pagada y decrementar stock...
                $factura = Factura::with('detalles.tomo')
                                  ->find($payment->external_reference);
                if ($factura) {
                    $factura->pagado = true;
                    $factura->save();

                    foreach ($factura->detalles as $detalle) {
                        $tomo = $detalle->tomo;
                        if ($tomo) {
                            $tomo->stock = max(0, $tomo->stock - $detalle->cantidad);
                            $tomo->save();
                            Log::info("📦 Tomo ID {$tomo->id} stock ajustado.");
                        }
                    }
                }
            }

        } catch (MPApiException $e) {
            Log::error("❌ Error al obtener el pago desde SDK: " . $e->getMessage());
            return response()->json(['message' => 'Error al obtener el pago'], 500);
        }

        return response()->json(['message' => 'Pago registrado en log'], 200);
    }
}

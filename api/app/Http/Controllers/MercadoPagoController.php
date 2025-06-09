<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Exceptions\MPApiException;

class MercadoPagoController extends Controller
{
    public function createPreference(Request $request)
    {
        // 1. Inicializar SDK
        MercadoPagoConfig::setAccessToken(env('MP_ACCESS_TOKEN'));

        // 2. Armar los ítems
        $items = [];
        foreach ($request->input('productos', []) as $prod) {
            $items[] = [
                'title'       => $prod['titulo'],
                'quantity'    => (int) $prod['cantidad'],
                'unit_price'  => (float) $prod['precio_unitario'],
                'currency_id' => 'ARS',
            ];
        }

        // 3. Crear la preferencia
        $preferenceData = [
            'items' => $items,
            'back_urls' => [
                'success' => 'http://localhost:3000/checkout/success',
                'failure' => 'http://localhost:3000/checkout/failure',
                'pending' => 'http://localhost:3000/checkout/pending',
            ],
           // 'auto_return' => 'approved',
        ];

        try {
            $client = new PreferenceClient();
            $preference = $client->create($preferenceData);

            return response()->json([
                'sandbox_init_point' => $preference->sandbox_init_point,
                'init_point' => $preference->init_point,
                'id' => $preference->id,
                'external_reference' => $preference->external_reference

            ]);
        } catch (MPApiException $e) {
            return response()->json([
                'message' => 'Error creando la preferencia de pago.',
                'errors' => $e->getApiResponse()->getContent(),
            ], 500);
        }
    }
}

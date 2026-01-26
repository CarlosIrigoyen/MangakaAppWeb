<?php

return [
    // rutas a las que aplica CORS (incluimos sanctum csrf-cookie si usás cookies)
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'paypal/*', 'mercadopago/*'],

    'allowed_methods' => ['*'],

    // NO usar '*' si vas a soportar credentials (cookies). Pon acá tus orígenes frontales.
    'allowed_origins' => [
        'https://mangakaappwebfront-production.up.railway.app',
        'http://localhost:3000'
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // si usás Laravel Sanctum con cookies -> true
    // si usás solo Bearer tokens, podés dejar false
    'supports_credentials' => true,
];

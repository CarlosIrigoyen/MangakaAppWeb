<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\TomoController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\MercadoPagoController;
use App\Models\Autor;
use App\Models\Manga;
use App\Models\Editorial;
use App\Models\Tomo;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Autenticación
Route::post('/register', [ClienteController::class, 'store']);
Route::post('/login',    [ClienteController::class, 'login']);
Route::post('/logout',   [ClienteController::class, 'logout'])->middleware('auth:sanctum');

// Usuario autenticado
Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    return response()->json($request->user());
});

// Rutas públicas
Route::get('public/tomos', [TomoController::class, 'indexPublic']);

Route::get('filters', function () {
    $authors     = Autor::select('id', 'nombre', 'apellido')->get();
    $mangas      = Manga::select('id', 'titulo')->get();
    $editorials  = Editorial::select('id', 'nombre')->get();
    $languages   = ['Español', 'Inglés', 'Japonés'];
    $minPrice    = Tomo::min('precio');
    $maxPrice    = Tomo::max('precio');

    return response()->json([
        'authors'    => $authors,
        'languages'  => $languages,
        'mangas'     => $mangas,
        'editorials' => $editorials,
        'minPrice'   => $minPrice,
        'maxPrice'   => $maxPrice,
    ]);
});

// Rutas protegidas (cliente logueado)
Route::middleware('auth:sanctum')->group(function () {
    // Checkout “manual” crea venta, factura y detalles, y decrementa stock
    Route::post('/checkout', [VentaController::class, 'checkout']);

    // Crear preferencia MercadoPago
    Route::post('/mercadopago/preference', [MercadoPagoController::class, 'createPreference']);

    // Confirmar pago desde el front en /checkout/success
    Route::post('/mercadopago/confirm', [MercadoPagoController::class, 'confirm']);

    // Listar facturas
    Route::get('/mis-facturas', [VentaController::class, 'indexFacturas'])
         ->name('mis-facturas.index');
    Route::get('/mis-facturas/{factura}', [VentaController::class, 'showFactura'])
         ->name('mis-facturas.show');
    Route::get('/mis-facturas/{factura}/pdf', [VentaController::class, 'descargarFactura'])
         ->name('mis-facturas.pdf');
});

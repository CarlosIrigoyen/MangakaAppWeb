<?php
// routes/api.php

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

Route::post('/register', [ClienteController::class, 'store']);
Route::post('/login',    [ClienteController::class, 'login']);

// --- RUTAS PÚBLICAS (ACCESIBLES SIN AUTENTICACIÓN) ---

Route::get('public/tomos', [TomoController::class, 'indexPublic']);
Route::get('filters', function () {
    $authors    = Autor::select('id','nombre','apellido')->get();
    $mangas     = Manga::select('id','titulo')->get();
    $editorials = Editorial::select('id','nombre')->get();
    $languages  = ['Español','Inglés','Japonés'];
    $minPrice   = Tomo::min('precio');
    $maxPrice   = Tomo::max('precio');
    return response()->json(compact('authors','languages','mangas','editorials','minPrice','maxPrice'));
});

// webhook público de MercadoPago (ngrok debe apuntar aquí)
Route::post('mercadopago/webhook', [MercadoPagoController::class, 'webhook']);

// --- RUTAS PROTEGIDAS (REQUIEREN AUTENTICACIÓN CON SANCTUM) ---
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [ClienteController::class, 'logout']);
    Route::get('/me', function (Request $request) {
        return response()->json($request->user());
    });

    Route::prefix('ventas')->group(function () {
        Route::post('checkout', [VentaController::class, 'checkout']);
        Route::get('mis-facturas',          [VentaController::class, 'indexFacturas'])
             ->name('mis-facturas.index');
        Route::get('mis-facturas/{factura}', [VentaController::class, 'showFactura'])
             ->name('mis-facturas.show');
    });

    // Crear preferencia de pago (requiere auth)
    Route::post('mercadopago/preference', [MercadoPagoController::class, 'createPreference']);
});

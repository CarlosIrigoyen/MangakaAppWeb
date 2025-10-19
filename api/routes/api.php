<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\TomoController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\MercadoPagoController;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\CarritoController; // ← AGREGAR
use App\Http\Controllers\FiltroController;
use App\Http\Controllers\PayPalController;
use App\Models\Autor;
use App\Models\Manga;
use App\Models\Editorial;
use App\Models\Tomo;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- RUTAS PÚBLICAS ---
// Registro y login de cliente
Route::post('/register', [ClienteController::class, 'store']);
Route::post('/login',    [ClienteController::class, 'login']);


// Listado público de tomos y filtros
Route::get('public/tomos', [TomoController::class, 'indexPublic']);


// Filtros - ahora usando el controlador
Route::get('filters', [FiltroController::class, 'getFilters']);
// Webhook público de MercadoPago
Route::post('mercadopago/webhook', [MercadoPagoController::class, 'webhook']);
Route::post('mercadopago/preference', [MercadoPagoController::class, 'createPreference']);

// --- RUTAS PROTEGIDAS (Sanctum) ---
Route::middleware('auth:sanctum')->group(function () {
    // Logout y usuario actual
    Route::post('/logout', [ClienteController::class, 'logout']);
    Route::get('/me', function (Request $request) {
        return response()->json($request->user());
    });

    // Facturación “directa” (sin integración activa de MercadoPago)
    Route::prefix('orders')->group(function () {
        // 1) Crear factura impaga + detalles + decrementar stock
        Route::post('checkout', [FacturaController::class, 'checkout'])
             ->name('orders.checkout');

        // 2) Listar facturas del cliente
        Route::get('invoices', [FacturaController::class, 'index'])
             ->name('orders.invoices.index');

        // 3) Mostrar detalle de una factura
        Route::get('invoices/{factura}', [FacturaController::class, 'show'])
             ->name('orders.invoices.show');
    });
    Route::post('/carrito/guardar', [CarritoController::class, 'guardarCarrito']);
    Route::get('/carrito/obtener/{clienteId}', [CarritoController::class, 'obtenerCarrito']);
    Route::delete('/carrito/limpiar/{clienteId}', [CarritoController::class, 'limpiarCarrito']);
    Route::middleware('auth:sanctum')
    ->post('/paypal/create-order', [PayPalController::class,'createOrder']);

});

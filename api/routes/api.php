<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\TomoController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\MercadoPagoController;
use App\Http\Controllers\PayPalController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\FiltroController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- RUTAS PÚBLICAS ---
Route::post('/register', [ClienteController::class, 'store']);
Route::post('/login',    [ClienteController::class, 'login']);
Route::get('public/tomos', [TomoController::class, 'indexPublic']);
Route::get('filters', [FiltroController::class, 'getFilters']);

// Webhooks públicos
Route::post('mercadopago/webhook', [MercadoPagoController::class, 'webhook']);
Route::post('paypal/webhook', [PayPalController::class, 'webhook']);

// --- RUTAS PROTEGIDAS (Sanctum) ---
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [ClienteController::class, 'logout']);
    Route::get('/me', function (Request $request) {
        return response()->json($request->user());
    });

    // Payment
    Route::post('mercadopago/preference', [MercadoPagoController::class, 'createPreference']);
    Route::post('paypal/create-order', [PayPalController::class, 'createOrder']);
    Route::post('paypal/capture-order/{orderId}', [PayPalController::class, 'captureOrder']);

    // Facturas (rutas originales)
    Route::prefix('orders')->group(function () {
        // Esta ruta es para crear facturas impagas (checkout directo, sin pasarela de pago?)
        Route::post('checkout', [FacturaController::class, 'checkout']);
        // Listar facturas del cliente
        Route::get('invoices', [FacturaController::class, 'index']);
        // Mostrar detalle de una factura
        Route::get('invoices/{factura}', [FacturaController::class, 'show']);
    });

    // Carrito
    Route::post('/carrito/guardar', [CarritoController::class, 'guardarCarrito']);
    Route::get('/carrito/obtener/{clienteId}', [CarritoController::class, 'obtenerCarrito']);
    Route::delete('/carrito/limpiar/{clienteId}', [CarritoController::class, 'limpiarCarrito']);
});

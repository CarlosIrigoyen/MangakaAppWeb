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
use App\Http\Controllers\SuscripcionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// --- RUTAS PÚBLICAS ---
Route::post('/register', [ClienteController::class, 'store']);
Route::post('/login',    [ClienteController::class, 'login']);

// Tomos públicos y filtros
Route::get('public/tomos', [TomoController::class, 'indexPublic']);
Route::get('filters', [FiltroController::class, 'getFilters']);

// Webhooks públicos
Route::post('mercadopago/webhook', [MercadoPagoController::class, 'webhook']);
Route::post('paypal/webhook', [PayPalController::class, 'webhook']);

// Ruta pública para el retorno de PayPal (redirecciona al frontend)
Route::get('paypal/return', [PayPalController::class, 'handleReturn']);

// --- RUTAS PROTEGIDAS (Sanctum) ---
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::post('/logout', [ClienteController::class, 'logout']);
    Route::get('/me', function (Request $request) {
        return response()->json($request->user());
    });

    // Payment: MercadoPago
    Route::post('mercadopago/preference', [MercadoPagoController::class, 'createPreference']);

    // Payment: PayPal
    Route::post('paypal/create-order', [PayPalController::class, 'createOrder']);
    Route::post('paypal/capture-order/{orderId}', [PayPalController::class, 'captureOrder']);
    Route::get('paypal/order/{orderId}', [PayPalController::class, 'getOrder']);

    // Debug / config (puedes eliminar en producción)
    Route::get('paypal/debug-config', [PayPalController::class, 'debugConfig']);

    // Facturas / Orders
    Route::prefix('orders')->group(function () {
        Route::post('checkout', [FacturaController::class, 'checkout']);
        Route::get('invoices', [FacturaController::class, 'index']);
        Route::get('invoices/{factura}', [FacturaController::class, 'show']);
    });

    // Carrito
    Route::post('/carrito/guardar', [CarritoController::class, 'guardarCarrito']);
    Route::get('/carrito/obtener/{clienteId}', [CarritoController::class, 'obtenerCarrito']);
    Route::delete('/carrito/limpiar/{clienteId}', [CarritoController::class, 'limpiarCarrito']);

    // Suscripciones - RUTAS COMPLETAS
    Route::prefix('suscripciones')->group(function () {
        Route::get('/mangas-disponibles', [SuscripcionController::class, 'mangasDisponibles']);
        Route::get('/mis-suscripciones', [SuscripcionController::class, 'misSuscripciones']);
        Route::post('/actualizar-suscripciones', [SuscripcionController::class, 'actualizarSuscripciones']);
        Route::post('/suscripcion-automatica', [SuscripcionController::class, 'manejarSuscripcionAutomatica']);
        Route::post('/suscribir', [SuscripcionController::class, 'suscribir']);
        Route::post('/desuscribir', [SuscripcionController::class, 'desuscribir']);
        Route::post('/actualizar-token', [SuscripcionController::class, 'actualizarToken']);

    });
});

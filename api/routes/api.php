<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\TomoController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\MercadoPagoController;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\CarritoController; // ← AGREGAR
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


Route::get('filters', function () {
    // === AUTORES CON AL MENOS UN MANGA ACTIVO Y AL MENOS UN TOMO ACTIVO ===
    $authors = Autor::select('id', 'nombre', 'apellido')
        ->where('activo', true)
        ->whereHas('mangas', function ($q) {
            $q->where('activo', true)
              ->whereHas('tomos', function ($q) {
                  $q->where('activo', true);
              });
        })
        ->get();

    // === MANGAS CON AUTOR ACTIVO Y AL MENOS UN TOMO ACTIVO ===
    $mangas = Manga::select('id', 'titulo')
        ->where('activo', true)
        ->whereHas('autor', function ($q) {
            $q->where('activo', true);
        })
        ->whereHas('tomos', function ($q) {
            $q->where('activo', true);
        })
        ->get();

    // === EDITORIALES CON AL MENOS UN TOMO ACTIVO ===
    $editorials = Editorial::select('id', 'nombre')
        ->where('activo', true)
        ->whereHas('tomos', function ($q) {
            $q->where('activo', true);
        })
        ->get();

    // === IDIOMAS Y RANGO DE PRECIOS ===
           // === IDIOMAS DE TOMOS ACTIVOS (MODIFICADO) ===
        $languages = Tomo::where('activo', true)
            ->distinct()
            ->pluck('idioma')
            ->filter() // Elimina valores null o vacíos
            ->values()
            ->toArray();

        // Si no hay idiomas, usar array vacío en lugar de los fijos
        if (empty($languages)) {
            $languages = [];
        }
    $minPrice = Tomo::where('activo', true)->min('precio');
    $maxPrice = Tomo::where('activo', true)->max('precio');

    return response()->json(compact('authors', 'languages', 'mangas', 'editorials', 'minPrice', 'maxPrice'));
});
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

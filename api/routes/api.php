<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\TomoController;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\MercadoPagoController;
use Illuminate\Support\Facades\Artisan;
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
    // 1️⃣ Traemos datos base
    $authors    = Autor::select('id','nombre','apellido')->with('mangas')->get();
    $mangas     = Manga::select('id','titulo','editorial_id')->withCount('tomos')->get();
    $editorials = Editorial::select('id','nombre')->withCount('tomos')->get();
    $languages  = ['Español','Inglés','Japonés'];
    $minPrice   = Tomo::min('precio');
    $maxPrice   = Tomo::max('precio');

    // 2️⃣ Filtramos autores: deben tener al menos un manga con tomos
    $authors = $authors->filter(function ($autor) {
        // Devuelve true si al menos uno de los mangas del autor tiene tomos
        return $autor->mangas->some(function ($manga) {
            return $manga->tomos()->exists();
        });
    })->values();

    // 3️⃣ Filtramos mangas: deben tener al menos un tomo
    $mangas = $mangas->filter(function ($manga) {
        return $manga->tomos_count > 0;
    })->values();

    // 4️⃣ Filtramos editoriales: deben tener al menos un tomo
    $editorials = $editorials->filter(function ($editorial) {
        return $editorial->tomos_count > 0;
    })->values();

    // 5️⃣ Devolvemos todo igual que antes (sin romper tu frontend)
    return response()->json(compact('authors','languages','mangas','editorials','minPrice','maxPrice'));
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
    Route::middleware('auth:sanctum')
    ->post('/paypal/create-order', [PayPalController::class,'createOrder']);

});

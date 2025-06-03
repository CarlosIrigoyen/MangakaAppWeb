<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\TomoController;
use App\Http\Controllers\VentaController;
use App\Models\Autor;
use App\Models\Manga;
use App\Models\Editorial;
use App\Models\Tomo;

// Rutas de autenticación con tokens
Route::post('/register', [ClienteController::class, 'store']);
Route::post('/login', [ClienteController::class, 'login']);
Route::post('/logout', [ClienteController::class, 'logout'])->middleware('auth:sanctum');

// Endpoint para obtener el usuario autenticado basado en el token
Route::middleware('auth:sanctum')->get('/me', function (Request $request) {
    return response()->json($request->user());
});

// Rutas públicas que no requieren autenticación
Route::get('public/tomos', [TomoController::class, 'indexPublic']);

Route::get('filters', function () {
    $authors = Autor::select('id', 'nombre', 'apellido')->get();
    $mangas = Manga::select('id', 'titulo')->get();
    $editorials = Editorial::select('id', 'nombre')->get();

    // Idiomas fijos o provenientes de la base de datos
    $languages = ['Español', 'Inglés', 'Japonés'];

    // Obtener el precio mínimo y máximo de los tomos
    $minPrice = Tomo::min('precio');
    $maxPrice = Tomo::max('precio');

    return response()->json([
        'authors'    => $authors,
        'languages'  => $languages,
        'mangas'     => $mangas,
        'editorials' => $editorials,
        'minPrice'   => $minPrice,
        'maxPrice'   => $maxPrice,
    ]);
});

Route::middleware('auth:sanctum')->group(function () {
    // Checkout crea venta, factura y detalles, y decrementa stock
    Route::post('/checkout', [VentaController::class, 'checkout']);

    // Listar todas las facturas del cliente
    Route::get('/mis-facturas', [VentaController::class, 'indexFacturas'])
         ->name('mis-facturas.index');

    // Mostrar el detalle de una factura en JSON
    Route::get('/mis-facturas/{factura}', [VentaController::class, 'showFactura'])
         ->name('mis-facturas.show');

    // Descargar la factura en PDF
    Route::get('/mis-facturas/{factura}/pdf', [VentaController::class, 'descargarFactura'])
         ->name('mis-facturas.pdf');
});

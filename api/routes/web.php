<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AutorController;
use App\Http\Controllers\DibujanteController;
use App\Http\Controllers\EditorialController;
use App\Http\Controllers\MangaController;
use App\Http\Controllers\GeneroController;
use App\Http\Controllers\TomoController;

/*
|--------------------------------------------------------------------------
| Rutas públicas (guest)
|--------------------------------------------------------------------------
| Sólo accesible si NO estás autenticado; las rutas de login/registro
| las maneja Jetstream/Fortify automáticamente.
*/
Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return view('welcome');
    })->name('welcome');
});

/*
|--------------------------------------------------------------------------
| Rutas protegidas por autenticación
|--------------------------------------------------------------------------
| Aquí va todo lo relativo al CRUD y al dashboard. Si no estás logeado,
| Laravel te redirige al login.
*/
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
     ->group(function () {

    // Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // CRUD de entidades
    Route::resource('autores', AutorController::class);
    Route::resource('dibujantes', DibujanteController::class);
    Route::resource('editoriales', EditorialController::class);
    Route::resource('generos', GeneroController::class);

    // Mangas (sin update en resource, lo definimos manual)
    Route::resource('mangas', MangaController::class)->except(['update']);
    Route::put('/mangas/{id}', [MangaController::class, 'update'])
         ->name('mangas.update');

    // Tomos: CRUD completo + operaciones especiales
    Route::resource('tomos', TomoController::class);

    // Actualización múltiple de stock
    Route::put('tomos/updateMultipleStock',
        [TomoController::class, 'updateMultipleStock'])
        ->name('tomos.updateMultipleStock');

    // Reactivar tomo inactivo
    Route::put('tomos/{tomo}/reactivate',
        [TomoController::class, 'reactivate'])
        ->name('tomos.reactivate');
});

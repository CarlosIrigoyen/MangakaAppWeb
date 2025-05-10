<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AutorController;
use App\Http\Controllers\DibujanteController;
use App\Http\Controllers\EditorialController;
use App\Http\Controllers\MangaController;
use App\Http\Controllers\GeneroController;
use App\Http\Controllers\TomoController;
Route::get('/', function () {
    return view('welcome');
});

Route::resource('autores', AutorController::class);
Route::resource('dibujantes', DibujanteController::class);
Route::resource('editoriales', EditorialController::class);
Route::resource('generos', GeneroController::class);
Route::resource('mangas', MangaController::class)->except(['update']);
Route::put('tomos/updateMultipleStock', [TomoController::class, 'updateMultipleStock'])->name('tomos.updateMultipleStock');
Route::resource('tomos', TomoController::class);
// Ruta para reactivar (dar de alta) un tomo inactivo
Route::put('tomos/{tomo}/reactivate', [TomoController::class, 'reactivate'])
     ->name('tomos.reactivate');
// Usamos PUT para la actualización del manga
Route::put('/mangas/{id}', [MangaController::class, 'update'])->name('mangas.update');


Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AutorController;
use App\Http\Controllers\DibujanteController;
use App\Http\Controllers\EditorialController;
use App\Http\Controllers\MangaController;
use App\Http\Controllers\GeneroController;
use App\Http\Controllers\TomoController;
use Illuminate\Auth\Events\Login;


/*
|--------------------------------------------------------------------------
|
|--------------------------------------------------------------------------
| Aquí va todo lo relativo a la autenticación y el login.
*/
//redirecciona al login
Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return redirect('login');
    });
});



/*
|--------------------------------------------------------------------------
| Rutas protegidas por autenticación
|--------------------------------------------------------------------------
| Aquí va todo lo relativo al CRUD y al dashboard
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

       // Actualización múltiple de stock
       Route::put('tomos/updateMultipleStock',
       [TomoController::class, 'updateMultipleStock'])
       ->name('tomos.updateMultipleStock');

    // Tomos: CRUD completo + operaciones especiales
    Route::resource('tomos', TomoController::class);


    // Reactivar tomo inactivo
    Route::put('tomos/{tomo}/reactivate',
        [TomoController::class, 'reactivate'])
        ->name('tomos.reactivate');

    // Para checkaer si el autor tiene mangas asociados
    Route::get('/autores/{id}/check-mangas', [AutorController::class, 'checkMangas'])
    ->name('autores.checkMangas');
    // Para checkaer si el dibujante tiene mangas asociados
    Route::get('/dibujantes/{id}/check-mangas', [DibujanteController::class, 'checkMangas'])
    ->name('dibujantes.checkMangas');
    // Para Géneros: ruta de chequeo de mangas asociados
    Route::get('/generos/{id}/check-mangas', [GeneroController::class, 'checkMangas'])
    ->name('generos.checkMangas');
    // Para Editoriales: ruta de chequeo de tomos asociados
    Route::get('/editoriales/{id}/check-tomos', [EditorialController::class, 'checkTomos'])
     ->name('editoriales.checkTomos');
     // Para Mangas: ruta de chequeo de tomos asociados
     Route::get('/mangas/{id}/check-tomos', [MangaController::class, 'checkTomos'])
     ->name('mangas.checkTomos');

     //Para Autores: ruta para reactivar autor
     Route::post('autores/{autor}/reactivate', [AutorController::class, 'reactivate'])
     ->name('autores.reactivate');
     // Reactivar dibujante
     Route::post('dibujantes/{dibujante}/reactivate', [DibujanteController::class, 'reactivate'])
     ->name('dibujantes.reactivate');
     // Reactivar editorial
     Route::post('editoriales/{editorial}/reactivate', [EditorialController::class, 'reactivate'])
     ->name('editoriales.reactivate');
     // Reactivar genero
     Route::post('generos/{genero}/reactivate', [GeneroController::class, 'reactivate'])
     ->name('generos.reactivate');
     // Reactivar manga
     Route::post('mangas/{manga}/reactivate', [MangaController::class, 'reactivate'])
     ->name('mangas.reactivate');

});

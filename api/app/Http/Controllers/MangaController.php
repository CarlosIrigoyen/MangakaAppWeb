<?php

namespace App\Http\Controllers;

use App\Models\Manga;
use App\Models\Autor;
use App\Models\Dibujante;
use App\Models\Genero;
use Illuminate\Http\Request;

class MangaController extends Controller
{
    public function index()
{
    // Obtener todos los mangas junto con sus relaciones
    $mangas = Manga::with(['autor', 'dibujante', 'generos'])->get();
    // Solo se muestran autores y dibujantes activos
    $autores = Autor::where('activo', true)->get();
    $dibujantes = Dibujante::where('activo', true)->get();
    $generos = Genero::all();

    return view('mangas.index', compact('mangas', 'autores', 'dibujantes', 'generos'));
}

public function store(Request $request){
    // Validación
    $request->validate([
        'titulo'       => 'required|string|max:255',
        'autor_id'     => 'required|exists:autores,id',
        'dibujante_id' => 'required|exists:dibujantes,id',
        'generos'      => 'required|array',
        'generos.*'    => 'exists:generos,id',

    ]);


    $en_publicacion = $request->input('en_publicacion', 'si');

    // Crear el nuevo Manga
    $manga = Manga::create([
        'titulo'         => $request->titulo,
        'autor_id'       => $request->autor_id,
        'dibujante_id'   => $request->dibujante_id,
        'en_publicacion' => $en_publicacion,
    ]);

    // Asociar géneros
    $manga->generos()->attach($request->generos);

    return redirect()
        ->route('mangas.index')
        ->with('success', 'Manga creado exitosamente.');
}


    public function edit($id)
    {
        // Obtener el manga a editar junto con sus relaciones
        $manga = Manga::with(['autor', 'dibujante', 'generos'])->findOrFail($id);
        // Solo se muestran autores y dibujantes activos
        $autores = Autor::where('activo', true)->get();
        $dibujantes = Dibujante::where('activo', true)->get();
        $generos = Genero::all();

        return view('mangas.edit', compact('manga', 'autores', 'dibujantes', 'generos'));
    }

    public function update(Request $request, $id)
{
    // Validar los datos del formulario, nuevamente se omite en_publicacion
    $request->validate([
        'titulo'         => 'required|string|max:255',
        'autor_id'       => 'required|exists:autores,id',
        'dibujante_id'   => 'required|exists:dibujantes,id',
        'generos'        => 'required|array',
        'generos.*'      => 'exists:generos,id',
    ]);

    // Obtener el manga a actualizar
    $manga = Manga::findOrFail($id);

    // Determinar el valor de en_publicacion de la misma forma
    $en_publicacion = $request->has('en_publicacion') ? 'si' : 'no';

    // Actualizar el manga
    $manga->update([
        'titulo'         => $request->titulo,
        'autor_id'       => $request->autor_id,
        'dibujante_id'   => $request->dibujante_id,
        'en_publicacion' => $en_publicacion,
    ]);

    // Actualizar la relación con los géneros
    $manga->generos()->sync($request->generos);

    return redirect()->route('mangas.index')->with('success', 'Manga actualizado exitosamente.');
}


    public function destroy($id)
    {
        // Eliminar el manga y desasociar los géneros
        $manga = Manga::findOrFail($id);
        $manga->generos()->detach();
        $manga->delete();

        return redirect()->route('mangas.index')->with('success', 'Manga eliminado exitosamente.');
    }
}

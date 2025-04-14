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

    public function store(Request $request)
    {
        // Validar los datos del formulario
        $request->validate([
            'titulo'         => 'required|string|max:255',
            'autor_id'       => 'required|exists:autores,id',
            'dibujante_id'   => 'required|exists:dibujantes,id',
            'generos'        => 'required|array',
            'generos.*'      => 'exists:generos,id',
            'en_publicacion' => 'sometimes|boolean',
        ]);

        // Crear el nuevo manga, asignando true por defecto si no se envía el campo
        $manga = Manga::create([
            'titulo'         => $request->titulo,
            'autor_id'       => $request->autor_id,
            'dibujante_id'   => $request->dibujante_id,
            'en_publicacion' => $request->has('en_publicacion') ? $request->en_publicacion : true,
        ]);

        // Asociar los géneros seleccionados
        $manga->generos()->attach($request->generos);

        return redirect()->route('mangas.index')->with('success', 'Manga creado exitosamente.');
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
        // Validar los datos del formulario
        $request->validate([
            'titulo'         => 'required|string|max:255',
            'autor_id'       => 'required|exists:autores,id',
            'dibujante_id'   => 'required|exists:dibujantes,id',
            'generos'        => 'required|array',
            'generos.*'      => 'exists:generos,id',
            'en_publicacion' => 'sometimes|boolean',
        ]);

        // Obtener y actualizar el manga
        $manga = Manga::findOrFail($id);
        $manga->update([
            'titulo'         => $request->titulo,
            'autor_id'       => $request->autor_id,
            'dibujante_id'   => $request->dibujante_id,
            'en_publicacion' => $request->has('en_publicacion'),
        ]);

        // Actualizar los géneros asociados
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

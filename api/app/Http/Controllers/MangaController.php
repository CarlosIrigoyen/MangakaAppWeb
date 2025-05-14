<?php

namespace App\Http\Controllers;

use App\Models\Manga;
use App\Models\Autor;
use App\Models\Dibujante;
use App\Models\Genero;
use Illuminate\Http\Request;

class MangaController extends Controller
{
    /**
     * Listado de mangas, filtrar por ?status=activo|inactivo
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'activo');

        if ($status === 'inactivo') {
            $mangas = Manga::inactivo()->with(['autor', 'dibujante', 'generos'])->get();
        } else {
            $mangas = Manga::activo()->with(['autor', 'dibujante', 'generos'])->get();
        }

        $autores     = Autor::where('activo', true)->get();
        $dibujantes  = Dibujante::where('activo', true)->get();
        $generos     = Genero::where('activo', true)->get();

        return view('mangas.index', compact('mangas', 'autores', 'dibujantes', 'generos', 'status'));
    }

    /**
     * Crear nuevo manga
     */
    public function store(Request $request)
    {
        $request->validate([
            'titulo'       => 'required|string|max:255',
            'autor_id'     => 'required|exists:autores,id',
            'dibujante_id' => 'required|exists:dibujantes,id',
            'generos'      => 'required|array',
            'generos.*'    => 'exists:generos,id',
        ]);

        $en_publicacion = $request->input('en_publicacion', 'si');

        $manga = Manga::create([
            'titulo'         => $request->titulo,
            'autor_id'       => $request->autor_id,
            'dibujante_id'   => $request->dibujante_id,
            'en_publicacion' => $en_publicacion,
            'activo'         => true,
        ]);

        $manga->generos()->attach($request->generos);

        return redirect()
            ->route('mangas.index', ['status' => 'activo'])
            ->with('success', 'Manga creado exitosamente.');
    }

    /**
     * Carga el formulario de edición
     */
    public function edit($id)
    {
        $manga      = Manga::with(['autor', 'dibujante', 'generos'])->findOrFail($id);
        $autores    = Autor::where('activo', true)->get();
        $dibujantes = Dibujante::where('activo', true)->get();
        $generos    = Genero::where('activo', true)->get();

        return view('mangas.edit', compact('manga', 'autores', 'dibujantes', 'generos'));
    }

    /**
     * Actualiza un manga
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'titulo'       => 'required|string|max:255',
            'autor_id'     => 'required|exists:autores,id',
            'dibujante_id' => 'required|exists:dibujantes,id',
            'generos'      => 'required|array',
            'generos.*'    => 'exists:generos,id',
        ]);

        $manga = Manga::findOrFail($id);

        $en_publicacion = $request->has('en_publicacion') ? 'si' : 'no';

        $manga->update([
            'titulo'         => $request->titulo,
            'autor_id'       => $request->autor_id,
            'dibujante_id'   => $request->dibujante_id,
            'en_publicacion' => $en_publicacion,
        ]);

        $manga->generos()->sync($request->generos);

        return redirect()
            ->route('mangas.index', ['status' => 'activo'])
            ->with('success', 'Manga actualizado exitosamente.');
    }

    /**
     * Inactivar un manga (en lugar de eliminarlo)
     */
    public function destroy($id)
    {
        $manga = Manga::findOrFail($id);
        $manga->activo = false;
        $manga->save();

        return redirect()
            ->route('mangas.index', ['status' => 'inactivo'])
            ->with('success', 'Manga inactivado correctamente.');
    }

    /**
     * Reactivar un manga inactivo
     */
    public function reactivate($id)
    {
        $manga = Manga::findOrFail($id);
        $manga->activo = true;
        $manga->save();

        return redirect()
            ->route('mangas.index', ['status' => 'inactivo'])
            ->with('success', 'Manga reactivado correctamente.');
    }

    /**
     * AJAX: contar tomos asociados antes de inactivar
     */
    public function checkTomos($id)
    {
        $tomosCount = \App\Models\Tomo::where('manga_id', $id)->count();
        $manga      = Manga::findOrFail($id);

        return response()->json([
            'tomos_count' => $tomosCount,
            'titulo'      => $manga->titulo,
        ]);
    }
}

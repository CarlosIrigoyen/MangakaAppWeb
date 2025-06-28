<?php

namespace App\Http\Controllers;

use App\Models\Genero;
use Illuminate\Http\Request;

class GeneroController extends Controller
{
    /**
     * Muestra la lista de géneros activos o inactivos según ?status=
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'activo');

        if ($status === 'inactivo') {
            $generos = Genero::inactivo()->get();
        } else {
            $generos = Genero::activo()->get();
        }

        return view('generos.index', compact('generos', 'status'));
    }

    /**
     * Almacena un nuevo género o reactiva uno existente.
     */
public function store(Request $request)
{
    // Validamos que recibimos al menos una cadena de texto
    $validated = $request->validate([
        'nombre' => 'required|string|max:1000', // permitimos una longitud mayor por la lista
    ]);

    // Separa por comas y limpia espacios en blanco
    $nombres = array_filter(array_map('trim', explode(',', $validated['nombre'])));

    if (empty($nombres)) {
        return back()
            ->withErrors(['nombre' => 'Debes indicar al menos un nombre válido.'])
            ->withInput();
    }

    $creados = $reactivados = [];

    foreach ($nombres as $nombre) {
        // Comprueba existencia (activo o inactivo)
        $g = Genero::where('nombre', $nombre)->first();

        if ($g) {
            if (! $g->activo) {
                $g->activo = true;
                $g->save();
                $reactivados[] = $nombre;
            }
        } else {
            Genero::create([
                'nombre' => $nombre,
                'activo' => true,
            ]);
            $creados[] = $nombre;
        }
    }

    // Construimos un mensaje de feedback
    $mensajes = [];
    if ($creados) {
        $mensajes[] = "Géneros creados: " . implode(', ', $creados);
    }
    if ($reactivados) {
        $mensajes[] = "Géneros reactivados: " . implode(', ', $reactivados);
    }

    return redirect()
        ->route('generos.index', ['status' => 'activo'])
        ->with('success', implode('. ', $mensajes) . '.');
}

    /**
     * Retorna JSON para el formulario de edición.
     */
    public function edit($id)
    {
        $genero = Genero::findOrFail($id);
        return response()->json($genero);
    }

    /**
     * Actualiza un género existente.
     */
    public function update(Request $request, $id)
    {
        $genero = Genero::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $genero->update($validated);

        return redirect()
            ->route('generos.index', ['status' => 'activo'])
            ->with('success', 'Género actualizado correctamente.');
    }

    /**
     * Inactiva un género (si no tiene mangas asociados).
     */
    public function destroy($id)
    {
        $genero = Genero::withCount('mangas')->findOrFail($id);

        if ($genero->mangas_count > 0) {
            return back()
                ->withErrors(['error' => "No se puede inactivar: tiene {$genero->mangas_count} manga(s) asociados."]);
        }

        $genero->activo = false;
        $genero->save();

        return redirect()
            ->route('generos.index', ['status' => 'inactivo'])
            ->with('success', 'Género inactivado correctamente.');
    }

    /**
     * Reactiva un género inactivo.
     */
    public function reactivate($id)
    {
        $genero = Genero::findOrFail($id);
        $genero->activo = true;
        $genero->save();

        return redirect()
            ->route('generos.index', ['status' => 'activo'])
            ->with('success', 'Género reactivado correctamente.');
    }

    /**
     * AJAX: devuelve el conteo de mangas asociados.
     */
    public function checkMangas($id)
    {
        $genero = Genero::withCount('mangas')->findOrFail($id);
        return response()->json([
            'mangas_count' => $genero->mangas_count,
            'nombre'       => $genero->nombre,
        ]);
    }
}

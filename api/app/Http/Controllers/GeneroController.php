<?php

namespace App\Http\Controllers;

use App\Models\Genero;
use App\Models\Manga;
use Illuminate\Http\Request;

class GeneroController extends Controller
{
    // Mostrar todos los géneros
    public function index()
    {
        $generos = Genero::all();
        return view('generos.index', compact('generos'));
    }

    // Mostrar el formulario para crear un nuevo género
    public function create()
    {
        return view('generos.create');
    }

    // Guardar un nuevo género
    public function store(Request $request)
    {
        // Validar que se reciba algún texto
        $request->validate([
            'nombre' => 'required|string',
        ]);

        // Dividir la cadena ingresada por comas y limpiar espacios
        $nombres = array_filter(array_map('trim', explode(',', $request->nombre)));

        // Acumular errores si algún género ya existe o por longitud
        $errores = [];

        foreach ($nombres as $nombre) {
            if (Genero::where('nombre', $nombre)->exists()) {
                $errores[] = "El género '{$nombre}' ya existe.";
                continue;
            }
            if (strlen($nombre) > 255) {
                $errores[] = "El género '{$nombre}' supera los 255 caracteres permitidos.";
                continue;
            }
            Genero::create(['nombre' => $nombre]);
        }

        if (count($errores) > 0) {
            return redirect()->route('generos.index')
                ->with('error', implode(' ', $errores));
        }

        return redirect()->route('generos.index')
            ->with('success', 'Géneros creados exitosamente');
    }

    // Mostrar el formulario para editar un género
    public function edit(Genero $genero)
    {
        if (request()->ajax()) {
            return response()->json($genero);
        }
        return view('generos.edit', compact('genero'));
    }

    // Actualizar un género
    public function update(Request $request, Genero $genero)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:generos,nombre,' . $genero->id,
        ]);

        $genero->update(['nombre' => $request->nombre]);

        return redirect()->route('generos.index')->with('success', 'Género actualizado exitosamente');
    }

    // Eliminar un género
    public function destroy(Genero $genero)
    {
        $genero->delete();
        return redirect()->route('generos.index')->with('success', 'Género eliminado exitosamente');
    }

    // Devuelve JSON con la cantidad de mangas asociados
    public function checkMangas($id)
    {
        $genero = Genero::withCount('mangas')->findOrFail($id);

        return response()->json([
            'mangas_count' => $genero->mangas_count,
            'nombre'       => $genero->nombre,
        ]);
    }
}

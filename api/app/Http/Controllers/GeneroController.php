<?php

namespace App\Http\Controllers;

use App\Models\Genero;
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

    // Opcional: acumular errores si algún género ya existe
    $errores = [];

    foreach ($nombres as $nombre) {
        // Validar que el nombre sea único
        if (Genero::where('nombre', $nombre)->exists()) {
            $errores[] = "El género '{$nombre}' ya existe.";
            continue;
        }

        // Validar longitud o cualquier otra regla adicional si es necesario
        if (strlen($nombre) > 255) {
            $errores[] = "El género '{$nombre}' supera los 255 caracteres permitidos.";
            continue;
        }

        // Si todo está ok, se crea el registro
        Genero::create([
            'nombre' => $nombre,
        ]);
    }

    // Si hubo errores, se puede redirigir de vuelta con un mensaje, por ejemplo:
    if (count($errores) > 0) {
        return redirect()->route('generos.index')
            ->with('error', implode(' ', $errores));
    }

    return redirect()->route('generos.index')
        ->with('success', 'Géneros creados exitosamente');
}


    // Mostrar el formulario para editar un género
    public function edit(Genero $genero){
        // Verifica si la petición es AJAX, pero si sabes que solo se usará vía AJAX, simplemente retorna el JSON.
        if (request()->ajax()) {
            return response()->json($genero);
        }

        // Opcional: Si se accede de forma tradicional, puedes retornar la vista
        return view('generos.edit', compact('genero'));
    }
    // Actualizar un género
    public function update(Request $request, Genero $genero)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:generos,nombre,' . $genero->id,
        ]);

        $genero->update([
            'nombre' => $request->nombre,
        ]);

        return redirect()->route('generos.index')->with('success', 'Género actualizado exitosamente');
    }

    // Eliminar un género
    public function destroy(Genero $genero)
    {
        $genero->delete();
        return redirect()->route('generos.index')->with('success', 'Género eliminado exitosamente');
    }
}

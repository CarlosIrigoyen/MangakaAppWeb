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
        $request->validate([
            'nombre' => 'required|string|max:255|unique:generos,nombre',
        ]);

        Genero::create([
            'nombre' => $request->nombre,
        ]);

        return redirect()->route('generos.index')->with('success', 'Género creado exitosamente');
    }

    // Mostrar el formulario para editar un género
    public function edit(Genero $genero)
    {
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

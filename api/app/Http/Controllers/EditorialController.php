<?php

namespace App\Http\Controllers;

use App\Models\Editorial;
use Illuminate\Http\Request;

class EditorialController extends Controller
{
    // Mostrar la lista de editoriales
    public function index()
    {
        $editoriales = Editorial::all();
        return view('editoriales.index', compact('editoriales'));
    }

    // Almacenar una nueva editorial
    public function store(Request $request)
    {
        // Validar los datos del formulario
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'pais' => ['required', 'string', 'max:255', 'regex:/^[^\d]+$/'], // No permite números
        ], [
            'pais.regex' => 'El país no puede contener números.',
        ]);

        // Crear la nueva editorial
        Editorial::create($validated);

        return redirect()->route('editoriales.index')->with('success', 'Editorial creada exitosamente.');
    }

    // Editar una editorial (obtener datos para el formulario)
    public function edit($id)
    {
        $editorial = Editorial::findOrFail($id);
        return response()->json($editorial);
    }

    // Actualizar los datos de una editorial
    public function update(Request $request, $id)
    {
        $editorial = Editorial::findOrFail($id);

        // Validar los datos enviados
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'pais' => ['required', 'string', 'max:255', 'regex:/^[^\d]+$/'], // No permite números
        ], [
            'pais.regex' => 'El país no puede contener números.',
        ]);

        // Actualizar los campos de la editorial
        $editorial->update($validated);

        return redirect()->route('editoriales.index')->with('success', 'Editorial actualizada correctamente.');
    }

    // Eliminar una editorial
    public function destroy($id)
    {
        $editorial = Editorial::findOrFail($id);
        $editorial->delete();

        return redirect()->route('editoriales.index')->with('success', 'Editorial eliminada correctamente.');
    }
}

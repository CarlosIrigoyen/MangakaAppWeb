<?php
namespace App\Http\Controllers;

use App\Models\Autor;
use Illuminate\Http\Request;
use Carbon\Carbon; // Asegúrate de importar Carbon

class AutorController extends Controller
{
    public function store(Request $request)
{
    // Validar los datos del formulario, incluyendo la validación de edad mínima
    $validated = $request->validate([
        'nombre'           => 'required|string|max:255',
        'apellido'         => 'required|string|max:255',
        'fecha_nacimiento' => 'required|date|before_or_equal:' . \Carbon\Carbon::now()->subYears(18)->toDateString(),
    ], [
        'fecha_nacimiento.before_or_equal' => 'El autor debe tener al menos 18 años.',
    ]);

    // Buscar si ya existe un autor con el mismo nombre, apellido y fecha de nacimiento
    $autor = \App\Models\Autor::where('nombre', $validated['nombre'])
                ->where('apellido', $validated['apellido'])
                ->where('fecha_nacimiento', $validated['fecha_nacimiento'])
                ->first();

    if ($autor) {
        // Si ya existe, se actualiza el campo activo a true
        $autor->activo = true;
        $autor->save();
    } else {
        // Crear el nuevo autor, estableciendo 'activo' como true por defecto
        \App\Models\Autor::create([
            'nombre'           => $validated['nombre'],
            'apellido'         => $validated['apellido'],
            'fecha_nacimiento' => $validated['fecha_nacimiento'],
            'activo'           => true,
        ]);
    }

    return redirect()->route('autores.index')->with('success', 'Autor creado exitosamente.');
}

    public function index(){
        // Obtener solo los autores que tienen 'activo' en true
        $autores = Autor::where('activo', true)->get();

        // Retornar la vista con los autores
        return view('autores.index', compact('autores'));
    }

    public function edit($id)
    {
        $autor = Autor::findOrFail($id);
        return response()->json($autor);
    }

    public function update(Request $request, $id)
    {
        // Obtener el autor por su ID
        $autor = Autor::findOrFail($id);

        // Validar los datos enviados
        $validated = $request->validate([
            'nombre'            => 'required|string|max:255',
            'apellido'          => 'required|string|max:255',
            'fecha_nacimiento'  => 'required|date|before_or_equal:' . Carbon::now()->subYears(18)->toDateString(),
        ], [
            'fecha_nacimiento.before_or_equal' => 'El autor debe tener al menos 18 años.',
        ]);

        // Actualizar los campos del autor
        $autor->nombre           = $validated['nombre'];
        $autor->apellido         = $validated['apellido'];
        $autor->fecha_nacimiento = $validated['fecha_nacimiento'];

        $autor->save();

        return redirect()->route('autores.index')->with('success', 'Autor actualizado correctamente.');
    }

    public function destroy($id)
    {
        // Encontrar el autor por su ID
        $autor = Autor::findOrFail($id);

        // En lugar de eliminar, se actualiza el campo 'activo' a false
        $autor->activo = false;
        $autor->save();

        return redirect()->route('autores.index')->with('success', 'Autor inactivado correctamente.');
    }
}

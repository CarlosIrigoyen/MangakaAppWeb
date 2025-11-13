<?php

namespace App\Http\Controllers;

use App\Models\Autor;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AutorController extends Controller
{
    /**
     * Mostrar listado de autores según estado: activo o inactivo.
     */
    //prueba de controller
    public function index(Request $request)
    {
        // Obtenemos el filtro de la query string, por defecto 'activo'
        $status = $request->get('status', 'activo');

        if ($status === 'inactivo') {
            $autores = Autor::inactivo()->get();
        } else {
            $autores = Autor::activo()->get();
        }

        return view('autores.index', compact('autores', 'status'));
    }

    /**
     * Almacenar un autor nuevo o reactivar uno existente.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'           => 'required|string|max:255',
            'apellido'         => 'required|string|max:255',
            'fecha_nacimiento' => 'required|date|before_or_equal:' . Carbon::now()->subYears(18)->toDateString(),
        ], [
            'fecha_nacimiento.before_or_equal' => 'El autor debe tener al menos 18 años.',
        ]);

        // Si ya existía (pero estaba inactivo), lo reactivamos
        $autor = Autor::where('nombre', $validated['nombre'])
            ->where('apellido', $validated['apellido'])
            ->where('fecha_nacimiento', $validated['fecha_nacimiento'])
            ->first();

        if ($autor) {
            $autor->activo = true;
            $autor->save();
        } else {
            Autor::create([
                'nombre'           => $validated['nombre'],
                'apellido'         => $validated['apellido'],
                'fecha_nacimiento' => $validated['fecha_nacimiento'],
                'activo'           => true,
            ]);
        }

        return redirect()
            ->route('autores.index', ['status' => 'activo'])
            ->with('success', 'Autor creado/reactivado exitosamente.');
    }

    /**
     * Cargar datos de un autor para el formulario de edición.
     */
    public function edit($id)
    {
        $autor = Autor::findOrFail($id);
        return response()->json($autor);
    }

    /**
     * Actualizar datos de un autor existente.
     */
    public function update(Request $request, $id)
    {
        $autor = Autor::findOrFail($id);

        $validated = $request->validate([
            'nombre'           => 'required|string|max:255',
            'apellido'         => 'required|string|max:255',
            'fecha_nacimiento' => 'required|date|before_or_equal:' . Carbon::now()->subYears(18)->toDateString(),
        ], [
            'fecha_nacimiento.before_or_equal' => 'El autor debe tener al menos 18 años.',
        ]);

        $autor->update([
            'nombre'           => $validated['nombre'],
            'apellido'         => $validated['apellido'],
            'fecha_nacimiento' => $validated['fecha_nacimiento'],
        ]);

        return redirect()
            ->route('autores.index', ['status' => 'activo'])
            ->with('success', 'Autor actualizado correctamente.');
    }

    /**
     * Inactivar (soft‐delete) un autor.
     */
    public function destroy($id)
    {
        $autor = Autor::findOrFail($id);
        $autor->activo = false;
        $autor->save();

        return redirect()
            ->route('autores.index', ['status' => 'inactivo'])
            ->with('success', 'Autor inactivado correctamente.');
    }

    /**
     * Reactivar un autor inactivo.
     */
    public function reactivate($id)
    {
        $autor = Autor::findOrFail($id);

        // Marcar como activo
        $autor->activo = true;
        $autor->save();

        // Redirigir al listado de activos
        return redirect()
            ->route('autores.index', ['status' => 'activo'])
            ->with('success', 'Autor reactivado correctamente.');
    }

    /**
     * Devuelve JSON con la cuenta de mangas asociados a un autor.
     */
    public function checkMangas($id)
    {
        $autor = Autor::withCount('mangas')->findOrFail($id);

        return response()->json([
            'mangas_count' => $autor->mangas_count,
            'nombre'       => $autor->nombre . ' ' . $autor->apellido,
        ]);
    }
}

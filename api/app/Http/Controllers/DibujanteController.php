<?php

namespace App\Http\Controllers;

use App\Models\Dibujante;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DibujanteController extends Controller
{
    /**
     * Muestra la lista de dibujantes activos o inactivos según ?status=
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'activo');

        if ($status === 'inactivo') {
            $dibujantes = Dibujante::inactivo()->get();
        } else {
            $dibujantes = Dibujante::activo()->get();
        }

        return view('dibujantes.index', compact('dibujantes', 'status'));
    }

    /**
     * Almacena un nuevo dibujante o reactiva uno existente.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'           => 'required|string|max:255|regex:/^[\p{L}\s]+$/u',
            'apellido'         => 'required|string|max:255|regex:/^[\p{L}\s]+$/u',
            'fecha_nacimiento' => 'required|date|before_or_equal:' . Carbon::now()->subYears(18)->toDateString(),
        ], [
            'fecha_nacimiento.before_or_equal' => 'El dibujante debe tener al menos 18 años.',
        ]);

        $dibujante = Dibujante::where('nombre', $validated['nombre'])
            ->where('apellido', $validated['apellido'])
            ->where('fecha_nacimiento', $validated['fecha_nacimiento'])
            ->first();

        if ($dibujante) {
            $dibujante->activo = true;
            $dibujante->save();
        } else {
            Dibujante::create([
                'nombre'           => $validated['nombre'],
                'apellido'         => $validated['apellido'],
                'fecha_nacimiento' => $validated['fecha_nacimiento'],
                'activo'           => true,
            ]);
        }

        return redirect()
            ->route('dibujantes.index', ['status' => 'activo'])
            ->with('success', 'Dibujante creado/reactivado exitosamente.');
    }

    /**
     * Retorna los datos de un dibujante en formato JSON para el formulario de edición.
     */
    public function edit($id)
    {
        $dibujante = Dibujante::findOrFail($id);
        return response()->json($dibujante);
    }

    /**
     * Actualiza los datos de un dibujante.
     */
    public function update(Request $request, $id)
    {
        $dibujante = Dibujante::findOrFail($id);

        $validated = $request->validate([
            'nombre'           => 'required|string|max:255|regex:/^[\p{L}\s]+$/u',
            'apellido'         => 'required|string|max:255|regex:/^[\p{L}\s]+$/u',
            'fecha_nacimiento' => 'required|date|before_or_equal:' . Carbon::now()->subYears(18)->toDateString(),
        ], [
            'fecha_nacimiento.before_or_equal' => 'El dibujante debe tener al menos 18 años.',
        ]);

        $nombre   = mb_convert_case(trim($validated['nombre']), MB_CASE_TITLE, "UTF-8");
        $apellido = mb_convert_case(trim($validated['apellido']), MB_CASE_TITLE, "UTF-8");

        $dibujante->update([
            'nombre'           => $nombre,
            'apellido'         => $apellido,
            'fecha_nacimiento' => $validated['fecha_nacimiento'],
        ]);

        return redirect()
            ->route('dibujantes.index', ['status' => 'activo'])
            ->with('success', 'Dibujante actualizado correctamente.');
    }

    /**
     * "Elimina" un dibujante (lo inactiva).
     */
    public function destroy($id)
    {
        $dibujante = Dibujante::findOrFail($id);
        $dibujante->activo = false;
        $dibujante->save();

        return redirect()
            ->route('dibujantes.index', ['status' => 'inactivo'])
            ->with('success', 'Dibujante dado de baja correctamente.');
    }

    /**
     * Reactivar un dibujante inactivo.
     */
    public function reactivate($id)
    {
        $dibujante = Dibujante::findOrFail($id);

        // Marcar como activo
        $dibujante->activo = true;
        $dibujante->save();

        // Redirigir al listado de dibujantes activos
        return redirect()
            ->route('dibujantes.index', ['status' => 'activo'])
            ->with('success', 'Dibujante reactivado correctamente.');
    }


    /**
     * Devuelve JSON con la cuenta de mangas asociados a un dibujante.
     */
    public function checkMangas($id)
    {
        $dibujante = Dibujante::withCount('mangas')->findOrFail($id);
        return response()->json([
            'mangas_count' => $dibujante->mangas_count,
            'nombre'       => $dibujante->nombre . ' ' . $dibujante->apellido,
        ]);
    }
}

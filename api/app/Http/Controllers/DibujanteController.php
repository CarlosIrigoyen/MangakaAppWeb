<?php

namespace App\Http\Controllers;

use App\Models\Dibujante;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DibujanteController extends Controller
{
    /**
     * Muestra la lista de dibujantes activos.
     */
    public function index()
    {
        // Listamos solo los dibujantes activos
        $dibujantes = Dibujante::where('activo', true)->get();
        return view('dibujantes.index', compact('dibujantes'));
    }

    /**
     * Almacena un nuevo dibujante o reactiva uno existente.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'            => 'required|string|max:255|regex:/^[\p{L}\s]+$/u',
            'apellido'          => 'required|string|max:255|regex:/^[\p{L}\s]+$/u',
            'fecha_nacimiento'  => 'required|date|before:' . Carbon::now()->subYears(18)->toDateString(),
        ]);

        // Transformación: primer letra mayúscula y el resto en minúsculas (para cada palabra)
        $nombre   = mb_convert_case(trim($validated['nombre']), MB_CASE_TITLE, "UTF-8");
        $apellido = mb_convert_case(trim($validated['apellido']), MB_CASE_TITLE, "UTF-8");

        // Verificar si ya existe un dibujante con estos datos
        $existing = Dibujante::where('nombre', $nombre)
                    ->where('apellido', $apellido)
                    ->where('fecha_nacimiento', $validated['fecha_nacimiento'])
                    ->first();

        if ($existing) {
            // Reactivar dibujante ya existente
            $existing->activo = true;
            $existing->save();
            return redirect()->route('dibujantes.index')->with('success', 'Dibujante reactivado exitosamente.');
        }

        // Crear nuevo dibujante con activo en true
        Dibujante::create([
            'nombre'            => $nombre,
            'apellido'          => $apellido,
            'fecha_nacimiento'  => $validated['fecha_nacimiento'],
            'activo'            => true,
        ]);

        return redirect()->route('dibujantes.index')->with('success', 'Dibujante creado exitosamente.');
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
            'nombre'            => 'required|string|max:255|regex:/^[\p{L}\s]+$/u',
            'apellido'          => 'required|string|max:255|regex:/^[\p{L}\s]+$/u',
            'fecha_nacimiento'  => 'required|date|before:' . Carbon::now()->subYears(18)->toDateString(),
        ]);

        // Transformar los valores recibidos
        $nombre   = mb_convert_case(trim($validated['nombre']), MB_CASE_TITLE, "UTF-8");
        $apellido = mb_convert_case(trim($validated['apellido']), MB_CASE_TITLE, "UTF-8");

        // Actualizar los datos (se mantiene el estado actual de 'activo')
        $dibujante->update([
            'nombre'           => $nombre,
            'apellido'         => $apellido,
            'fecha_nacimiento' => $validated['fecha_nacimiento'],
        ]);

        return redirect()->route('dibujantes.index')->with('success', 'Dibujante actualizado correctamente.');
    }

    /**
     * "Elimina" un dibujante estableciendo el campo activo en false.
     */
    public function destroy($id)
    {
        $dibujante = Dibujante::findOrFail($id);
        $dibujante->activo = false;
        $dibujante->save();

        return redirect()->route('dibujantes.index')->with('success', 'Dibujante dado de baja correctamente.');
    }
}

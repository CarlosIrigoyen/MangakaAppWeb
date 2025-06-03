<?php

namespace App\Http\Controllers;

use App\Models\Editorial;
use App\Models\Tomo;
use Illuminate\Http\Request;

class EditorialController extends Controller
{
    /**
     * Listado de editoriales, filtrando por activo/inactivo.
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'activo');

        if ($status === 'inactivo') {
            $editoriales = Editorial::inactivo()->get();
        } else {
            $editoriales = Editorial::activo()->get();
        }

        return view('editoriales.index', compact('editoriales', 'status'));
    }

    /**
     * Almacenar (o reactivar) una editorial.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'pais'   => ['required','string','max:255','regex:/^[^\d]+$/'],
        ], [
            'pais.regex' => 'El país no puede contener números.',
        ]);
        $editorial = Editorial::where('nombre', $validated['nombre'])
                      ->where('pais', $validated['pais'])
                      ->first();

        if ($editorial) {
            $editorial->activo = true;
            $editorial->save();
        } else {
            Editorial::create($validated + ['activo' => true]);
        }

        return redirect()
            ->route('editoriales.index', ['status' => 'activo'])
            ->with('success', 'Editorial creada/reactivada exitosamente.');
    }

    /**
     * Devuelve JSON para el formulario de edición.
     */
    public function edit($id)
    {
        $editorial = Editorial::findOrFail($id);
        return response()->json($editorial);
    }

    /**
     * Actualizar datos de una editorial.
     */
    public function update(Request $request, $id)
    {
        $editorial = Editorial::findOrFail($id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'pais'   => ['required','string','max:255','regex:/^[^\d]+$/'],
        ], [
            'pais.regex' => 'El país no puede contener números.',
        ]);

        $editorial->update($validated);

        return redirect()
            ->route('editoriales.index', ['status' => 'activo'])
            ->with('success', 'Editorial actualizada correctamente.');
    }

    /**
     * Inactivar una editorial.
     */
    public function destroy($id)
    {
        $editorial = Editorial::findOrFail($id);

        // Si tiene tomos asociados, no permitir
        $tomosCount = Tomo::where('editorial_id', $id)->count();
        if ($tomosCount > 0) {
            return back()
                ->withErrors(['error' => "No se puede inactivar: tiene $tomosCount tomo(s) asociados."]);
        }

        $editorial->activo = false;
        $editorial->save();

        return redirect()
            ->route('editoriales.index', ['status' => 'inactivo'])
            ->with('success', 'Editorial inactivada correctamente.');
    }

    /**
     * Reactivar una editorial inactiva.
     */
    public function reactivate($id)
    {
        $editorial = Editorial::findOrFail($id);
        $editorial->activo = true;
        $editorial->save();

        return redirect()
            ->route('editoriales.index', ['status' => 'activo']) // <-- cambiar a 'activo'
            ->with('success', 'Editorial reactivada correctamente.');
    }

    /**
     * Comprueba via AJAX cuántos tomos tiene la editorial.
     */
    public function checkTomos($id)
    {
        $count = Tomo::where('editorial_id', $id)->count();
        $editorial = Editorial::findOrFail($id);

        return response()->json([
            'tomos_count' => $count,
            'nombre'      => $editorial->nombre,
        ]);
    }
}

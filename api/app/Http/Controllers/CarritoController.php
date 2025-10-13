<?php

namespace App\Http\Controllers;

use App\Models\Carrito;
use App\Models\Tomo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CarritoController extends Controller
{
    public function guardarCarrito(Request $request)
    {
        $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'carrito' => 'required|array',
            'carrito.*.id' => 'required|exists:tomos,id',
            'carrito.*.quantity' => 'required|integer|min:1'
        ]);

        try {
            DB::beginTransaction();

            $clienteId = $request->cliente_id;

            // Eliminar carrito anterior
            Carrito::where('cliente_id', $clienteId)->delete();

            // Guardar nuevo carrito con stock actualizado
            foreach ($request->carrito as $item) {
                // Verificar stock actual del tomo
                $tomo = Tomo::find($item['id']);

                if ($tomo && $tomo->stock > 0) {
                    // No permitir más de lo que hay en stock
                    $cantidad = min($item['quantity'], $tomo->stock);

                    Carrito::create([
                        'cliente_id' => $clienteId,
                        'tomo_id' => $item['id'],
                        'cantidad' => $cantidad
                    ]);
                }
            }

            DB::commit();

            return response()->json(['message' => 'Carrito guardado exitosamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al guardar carrito: ' . $e->getMessage()], 500);
        }
    }

    public function obtenerCarrito($clienteId)
    {
        $carritos = Carrito::with(['tomo.manga', 'tomo.editorial'])
            ->where('cliente_id', $clienteId)
            ->get()
            ->map(function ($carrito) {
                $tomo = $carrito->tomo;
                return [
                    'id' => $tomo->id,
                    'numero_tomo' => $tomo->numero_tomo,
                    'precio' => $tomo->precio,
                    'idioma' => $tomo->idioma,
                    'stock' => $tomo->stock, // Stock actualizado
                    'portada' => $tomo->portada,
                    'quantity' => $carrito->cantidad,
                    'manga' => $tomo->manga ? [
                        'titulo' => $tomo->manga->titulo
                    ] : null,
                    'editorial' => $tomo->editorial ? [
                        'nombre' => $tomo->editorial->nombre
                    ] : null
                ];
            });

        return response()->json($carritos);
    }

    public function limpiarCarrito($clienteId)
    {
        try {
            Carrito::where('cliente_id', $clienteId)->delete();
            return response()->json(['message' => 'Carrito limpiado exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al limpiar carrito'], 500);
        }
    }
}

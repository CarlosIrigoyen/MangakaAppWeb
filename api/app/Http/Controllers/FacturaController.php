<?php
// app/Http/Controllers/FacturaController.php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\DetalleFactura;
use App\Models\Tomo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FacturaController extends Controller
{
    /**
     * POST /api/orders/checkout
     * Crea una factura impaga + DetalleFactura + decrementa stock.
     * Body JSON: { items: [ { id: tomo_id, quantity } ] }
     */
    public function checkout(Request $request)
    {
        $user  = $request->user();
        $items = $request->input('items');

        DB::beginTransaction();
        try {
            // 1) Verificar stock y calcular total
            $total = 0;
            foreach ($items as $item) {
                $tomo = Tomo::lockForUpdate()->findOrFail($item['id']);
                if ($tomo->stock < $item['quantity']) {
                    return response()->json([
                        'message' => "Stock insuficiente para el tomo ID {$tomo->id}"
                    ], 400);
                }
                $total += $tomo->precio * $item['quantity'];
            }

            // 2) Crear factura (pagado = false)
            $factura = Factura::create([
                'numero'     => uniqid('FAC-'),
                'cliente_id' => $user->id,
                'pagado'     => false,
            ]);

            // 3) Crear cada detalle y decrementar stock
            foreach ($items as $item) {
                $tomo     = Tomo::findOrFail($item['id']);
                $cantidad = $item['quantity'];
                $precio   = $tomo->precio;
                $subtotal = $precio * $cantidad;

                DetalleFactura::create([
                    'factura_id'      => $factura->id,
                    'tomo_id'         => $tomo->id,
                    'cantidad'        => $cantidad,
                    'precio_unitario' => $precio,
                    'subtotal'        => $subtotal,
                ]);

                $tomo->decrement('stock', $cantidad);
            }

            DB::commit();

            return response()->json([
                'message'    => 'Factura creada con éxito.',
                'factura_id' => $factura->id,
                'numero'     => $factura->numero,
                'total'      => $total,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al procesar la factura.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/orders/invoices
     * Lista las facturas pagadas del cliente autenticado.
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $facturas = Factura::where('cliente_id', $userId)
                           ->where('pagado', true)
                           ->with('detalles')
                           ->orderBy('created_at', 'desc')
                           ->get();

        return response()->json($facturas->map(function($f) {
            return [
                'id'         => $f->id,
                'numero'     => $f->numero,
                'total'      => $f->detalles->sum('subtotal'),
                'created_at' => $f->created_at->toDateTimeString(),
            ];
        }));
    }

    /**
     * GET /api/orders/invoices/{factura}
     * Muestra el detalle completo de una factura del cliente.
     */
    public function show(Request $request, Factura $factura)
    {
        if ($factura->cliente_id !== $request->user()->id) {
            return response()->json(['message' => 'Acceso denegado'], 403);
        }

        $factura->load('detalles.tomo.manga');

        return response()->json([
            'id'       => $factura->id,
            'numero'   => $factura->numero,
            'fecha'    => $factura->created_at->toDateTimeString(),
            'cliente'  => [
                'nombre'   => $factura->cliente->nombre,
                'apellido' => $factura->cliente->apellido,
            ],
            'detalles' => $factura->detalles->map(fn($d) => [
                'tomo_id'         => $d->tomo_id,
                'titulo'          => $d->tomo->manga->titulo,
                'numero_tomo'     => $d->tomo->numero_tomo,
                'cantidad'        => $d->cantidad,
                'precio_unitario' => $d->precio_unitario,
                'subtotal'        => $d->subtotal,
            ])->values(),
            'total'    => $factura->detalles->sum('subtotal'),
        ]);
    }
}

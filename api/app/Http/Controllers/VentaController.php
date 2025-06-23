<?php

namespace App\Http\Controllers;

use App\Models\DetalleFactura;
use App\Models\Venta;
use App\Models\Factura;
use App\Models\DetalleVenta;
use App\Models\Tomo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;
class VentaController extends Controller
{
    public function checkout(Request $request)
    {
        $user  = $request->user();
        $items = $request->input('items');

        DB::beginTransaction();

        try {
            // 1) Calcular total y verificar stock
            //agreado script de json para probar la api
            $totalVenta = 0;
            foreach ($items as $item) {
                $tomo = Tomo::findOrFail($item['id']);
                if ($tomo->stock < $item['quantity']) {
                    return response()->json([
                        'message' => "Stock insuficiente para el tomo ID {$tomo->id}"
                    ], 400);
                }
                $totalVenta += $tomo->precio * $item['quantity'];
            }

            // 2) Crear Venta
            $venta = Venta::create([
                'cliente_id' => $user->id,
                'total'      => $totalVenta,
            ]);

            // 3) Crear Factura
            $factura = Factura::create([
                'venta_id' => $venta->id,
                'numero'   => uniqid('FAC-'),
            ]);

            // 4) Crear Detalles y decrementar stock
            foreach ($items as $item) {
                $tomo     = Tomo::findOrFail($item['id']);
                $cantidad = $item['quantity'];
                $precio   = $tomo->precio;
                $subtotal = $precio * $cantidad;

                DetalleVenta::create([
                    'venta_id'        => $venta->id,
                    'tomo_id'         => $tomo->id,
                    'cantidad'        => $cantidad,
                    'precio_unitario'=> $precio,
                ]);

                DetalleFactura::create([
                    'factura_id'      => $factura->id,
                    'tomo_id'         => $tomo->id,
                    'cantidad'        => $cantidad,
                    'precio_unitario'=> $precio,
                    'subtotal'        => $subtotal,
                ]);

                $tomo->decrement('stock', $cantidad);
            }

            DB::commit();

            return response()->json([
                'message'    => 'Compra registrada con éxito.',
                'venta_id'   => $venta->id,
                'factura_id' => $factura->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error al procesar la compra.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    public function indexFacturas(Request $request)
    {
        $user = $request->user();

        // Traer facturas asociadas a las ventas de este cliente
        $facturas = Factura::whereHas('venta', function ($q) use ($user) {
                $q->where('cliente_id', $user->id);
            })
            ->with('venta') // para obtener el total desde la relación Venta
            ->orderBy('created_at', 'desc')
            ->get(['id', 'venta_id', 'created_at']);

        // Formatear la respuesta
        $response = $facturas->map(function ($f) {
            return [
                'id'         => $f->id,
                'total'      => $f->venta->total,
                'created_at' => $f->created_at->toDateTimeString(),
            ];
        });

        return response()->json($response);
    }

    /**
     * GET /api/mis-facturas/{factura}
     * Devuelve el detalle completo de una factura en JSON.
     */
    public function showFactura(Request $request, Factura $factura)
    {
        // Validar que la factura pertenece al cliente
        if ($factura->venta->cliente_id !== $request->user()->id) {
            return response()->json(['message' => 'Acceso denegado'], 403);
        }

        // Cargar relaciones necesarias
        $factura->load('venta.cliente', 'detalles.tomo.manga');

        // Armar payload
        $payload = [
            'id'      => $factura->id,
            'numero'  => $factura->numero,
            'cliente' => [
                'id'     => $factura->venta->cliente->id,
                'nombre' => $factura->venta->cliente->nombre,
                'email'  => $factura->venta->cliente->email,
            ],
            'fecha'    => $factura->created_at->toDateTimeString(),
            'total'    => $factura->venta->total,
            'detalles' => $factura->detalles->map(function ($d) {
                return [
                    'tomo_id'         => $d->tomo_id,
                    'titulo'          => $d->tomo->manga->titulo,
                    'numero_tomo'     => $d->tomo->numero_tomo,
                    'cantidad'        => $d->cantidad,
                    'precio_unitario' => $d->precio_unitario,
                    'subtotal'        => $d->subtotal,
                ];
            }),
        ];

        return response()->json($payload);
    }

    /**
     * GET /api/mis-facturas/{factura}/pdf
     * Genera y devuelve la factura en PDF para descarga.
     */

}

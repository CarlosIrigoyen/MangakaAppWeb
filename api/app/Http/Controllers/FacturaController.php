<?php
// app/Http/Controllers/FacturaController.php
namespace App\Http\Controllers;

use App\Models\Factura;
use Illuminate\Http\Request;

class FacturaController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $facturas = Factura::where('cliente_id', $userId)
                    ->where('pagado', true)
                    ->with('detalles.tomo.manga')
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

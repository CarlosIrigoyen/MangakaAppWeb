<?php
// app/Http/Controllers/FacturaController.php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\DetalleFactura;
use App\Models\Tomo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class FacturaController extends Controller
{
    /**
     * Crear factura para cualquier método de pago
     * Body JSON: {
     *   cliente_id: int,
     *   productos: [ { tomo_id, titulo, cantidad, precio_unitario } ],
     *   metodo_pago: 'mercadopago'|'paypal'
     * }
     */
    public function crearFacturaParaPago(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'cliente_id' => 'required|integer|exists:clientes,id',
                'productos' => 'required|array|min:1',
                'productos.*.tomo_id' => 'required|integer|exists:tomos,id',
                'productos.*.titulo' => 'required|string',
                'productos.*.cantidad' => 'required|integer|min:1',
                'productos.*.precio_unitario' => 'required|numeric|min:0',
                'metodo_pago' => 'required|string|in:mercadopago,paypal'
            ]);

            $clienteId = $request->input('cliente_id');
            $metodoPago = $request->input('metodo_pago');
            $productos = $request->input('productos', []);

            Log::info("🆕 Creando factura para cliente {$clienteId} con método: {$metodoPago}");

            // 1) Verificar stock
            foreach ($productos as $prod) {
                $tomo = Tomo::lockForUpdate()->findOrFail($prod['tomo_id']);
                if ($tomo->stock < $prod['cantidad']) {
                    return response()->json([
                        'message' => "Stock insuficiente para el tomo ID {$tomo->id}",
                        'tomo_id' => $tomo->id,
                        'stock_disponible' => $tomo->stock,
                        'cantidad_solicitada' => $prod['cantidad']
                    ], 400);
                }
            }

            // 2) Crear factura
            $factura = Factura::create([
                'numero' => $this->generarNumeroFactura($metodoPago),
                'cliente_id' => $clienteId,
                'pagado' => false,
                'metodo_pago' => $metodoPago,
                'external_reference' => Str::uuid()->toString(),
            ]);

            // 3) Crear detalles
            $total = 0;
            foreach ($productos as $prod) {
                $subtotal = (float) $prod['cantidad'] * $prod['precio_unitario'];
                $total += $subtotal;

                DetalleFactura::create([
                    'factura_id' => $factura->id,
                    'tomo_id' => $prod['tomo_id'],
                    'cantidad' => (int) $prod['cantidad'],
                    'precio_unitario' => (float) $prod['precio_unitario'],
                    'subtotal' => $subtotal,
                ]);

                Log::info("📦 Detalle factura creado: Tomo {$prod['tomo_id']} x {$prod['cantidad']}");
            }

            DB::commit();

            Log::info("✅ Factura {$factura->id} creada exitosamente. Total: {$total}");

            return response()->json([
                'message' => 'Factura creada con éxito.',
                'factura_id' => $factura->id,
                'numero' => $factura->numero,
                'external_reference' => $factura->external_reference,
                'total' => $total,
                'metodo_pago' => $metodoPago,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error al crear factura: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al crear la factura.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marcar factura como pagada y decrementar stock
     */
    public function marcarComoPagada(Request $request, $facturaId)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'payment_id' => 'sometimes|string',
                'fecha_pago' => 'sometimes|date'
            ]);

            $factura = Factura::with('detalles.tomo')->findOrFail($facturaId);

            if ($factura->pagado) {
                return response()->json(['message' => 'La factura ya está pagada.'], 400);
            }

            $factura->pagado = true;
            $factura->fecha_pago = $request->input('fecha_pago', now());
            $factura->payment_id = $request->input('payment_id');
            $factura->save();

            Log::info("💰 Marcando factura {$facturaId} como pagada");

            // Decrementar stock
            foreach ($factura->detalles as $detalle) {
                $tomo = $detalle->tomo;
                if ($tomo) {
                    $tomo->decrement('stock', $detalle->cantidad);
                    Log::info("📦 Stock decrementado - Tomo {$tomo->id}: -{$detalle->cantidad}, Stock restante: {$tomo->stock}");
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Factura marcada como pagada exitosamente.',
                'factura' => [
                    'id' => $factura->id,
                    'numero' => $factura->numero,
                    'metodo_pago' => $factura->metodo_pago,
                    'total' => $factura->detalles->sum('subtotal')
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error al marcar factura como pagada: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al marcar la factura como pagada.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener factura por external_reference
     */
    public function obtenerPorReferencia($externalReference)
    {
        $factura = Factura::with(['detalles.tomo.manga', 'cliente'])
                         ->where('external_reference', $externalReference)
                         ->firstOrFail();

        return response()->json([
            'id' => $factura->id,
            'numero' => $factura->numero,
            'metodo_pago' => $factura->metodo_pago,
            'pagado' => $factura->pagado,
            'fecha' => $factura->created_at->toDateTimeString(),
            'fecha_pago' => $factura->fecha_pago?->toDateTimeString(),
            'cliente' => [
                'nombre' => $factura->cliente->nombre,
                'apellido' => $factura->cliente->apellido,
            ],
            'detalles' => $factura->detalles->map(fn($d) => [
                'tomo_id' => $d->tomo_id,
                'titulo' => $d->tomo->manga->titulo,
                'numero_tomo' => $d->tomo->numero_tomo,
                'cantidad' => $d->cantidad,
                'precio_unitario' => $d->precio_unitario,
                'subtotal' => $d->subtotal,
            ])->values(),
            'total' => $factura->detalles->sum('subtotal'),
        ]);
    }

    /**
     * Listar facturas pagadas del cliente
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
                'id' => $f->id,
                'numero' => $f->numero,
                'total' => $f->detalles->sum('subtotal'),
                'metodo_pago' => $f->metodo_pago,
                'created_at' => $f->created_at->toDateTimeString(),
                'fecha_pago' => $f->fecha_pago?->toDateTimeString(),
            ];
        }));
    }

    /**
     * Mostrar detalle completo de factura
     */
    public function show(Request $request, Factura $factura)
    {
        if ($factura->cliente_id !== $request->user()->id) {
            return response()->json(['message' => 'Acceso denegado'], 403);
        }

        $factura->load('detalles.tomo.manga', 'cliente');

        return response()->json([
            'id' => $factura->id,
            'numero' => $factura->numero,
            'metodo_pago' => $factura->metodo_pago,
            'fecha' => $factura->created_at->toDateTimeString(),
            'fecha_pago' => $factura->fecha_pago?->toDateTimeString(),
            'cliente' => [
                'nombre' => $factura->cliente->nombre,
                'apellido' => $factura->cliente->apellido,
            ],
            'detalles' => $factura->detalles->map(fn($d) => [
                'tomo_id' => $d->tomo_id,
                'titulo' => $d->tomo->manga->titulo,
                'numero_tomo' => $d->tomo->numero_tomo,
                'cantidad' => $d->cantidad,
                'precio_unitario' => $d->precio_unitario,
                'subtotal' => $d->subtotal,
            ])->values(),
            'total' => $factura->detalles->sum('subtotal'),
        ]);
    }

    /**
     * Generar número de factura según método de pago
     */
    private function generarNumeroFactura($metodoPago)
    {
        $prefix = match($metodoPago) {
            'paypal' => 'PP-',
            'mercadopago' => 'MP-',
            default => 'FAC-'
        };

        return $prefix . now()->format('Ymd-His') . '-' . Str::upper(Str::random(4));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\DetalleFactura;
use App\Models\Venta;
use App\Models\Tomo;
use App\Models\Manga;
use App\Models\Cliente;
use App\Models\Editorial;
use App\Models\Genero;
use App\Models\Autor;
use App\Models\Dibujante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Mostrar el dashboard (render server-side del Top mangas)
     */
    public function index(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $limit = (int) $request->input('limit', 5);

        // Años disponibles para el select
        $añosDisponibles = Factura::selectRaw('EXTRACT(YEAR FROM created_at) as año')
            ->distinct()
            ->orderBy('año', 'desc')
            ->pluck('año');

        // Estadísticas básicas por año
        $totalFacturas = Factura::whereYear('created_at', $year)->count();
        $totalClientes = Cliente::count();
        $totalTomos = Tomo::count();

        // Total de ingresos (por año) a partir de detalles
        $totalIngresos = Factura::whereYear('created_at', $year)
            ->with('detalles')
            ->get()
            ->flatMap->detalles
            ->sum('subtotal');

        // Ventas mensuales (pluck para gráficos en blade si hace falta)
        $ventasMensuales = Factura::selectRaw('EXTRACT(MONTH FROM facturas.created_at) as mes, SUM(factura_detalle.subtotal) as total')
            ->join('factura_detalle', 'factura_detalle.factura_id', '=', 'facturas.id')
            ->whereYear('facturas.created_at', $year)
            ->groupBy('mes')
            ->orderBy('mes')
            ->pluck('total', 'mes');

        // TOP MANGAS (server-side) usando factura_detalle
        // IMPORTANTE: la columna `portada` está en `tomos`, no en `mangas`.
        // Para evitar errores de GROUP BY, usamos MAX(tomos.portada) para traer una portada representativa por manga.
        $topMangas = DetalleFactura::join('facturas', 'factura_detalle.factura_id', '=', 'facturas.id')
            ->join('tomos', 'factura_detalle.tomo_id', '=', 'tomos.id')
            ->join('mangas', 'tomos.manga_id', '=', 'mangas.id')
            ->select(
                'mangas.id',
                'mangas.titulo',
                DB::raw('MAX(tomos.portada) as portada'),
                DB::raw('SUM(factura_detalle.cantidad) as total_vendido'),
                DB::raw('SUM(factura_detalle.cantidad * factura_detalle.precio_unitario) as ingresos_totales')
            )
            ->whereYear('facturas.created_at', $year)
            ->groupBy('mangas.id', 'mangas.titulo')
            ->orderByDesc('total_vendido')
            ->take($limit)
            ->get();

        // Pasar todo a la vista
        return view('dashboard', compact(
            'year',
            'limit',
            'añosDisponibles',
            'totalFacturas',
            'totalClientes',
            'totalTomos',
            'totalIngresos',
            'ventasMensuales',
            'topMangas'
        ));
    }

    /**
     * Obtener ventas mensuales (JSON) — usado por los gráficos si querés seguir con AJAX
     */
    public function getVentasMensuales(Request $request)
    {
        $year = $request->input('year', date('Y'));

        // Obtener años disponibles
        $añosDisponibles = Factura::selectRaw('EXTRACT(YEAR FROM created_at) as año')
            ->distinct()
            ->orderBy('año', 'desc')
            ->pluck('año');

        // Obtener datos mensuales para gráficos
        $ventasMensuales = DB::table('facturas')
            ->select(
                DB::raw('EXTRACT(MONTH FROM facturas.created_at) as mes'),
                DB::raw('COUNT(DISTINCT facturas.id) as total_ventas'),
                DB::raw('COALESCE(SUM(factura_detalle.subtotal), 0) as monto_total'),
                DB::raw('COALESCE(SUM(factura_detalle.cantidad), 0) as total_tomos')
            )
            ->leftJoin('factura_detalle', 'facturas.id', '=', 'factura_detalle.factura_id')
            ->whereYear('facturas.created_at', $year)
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        // Asegurar los 12 meses
        $mesesCompletos = [];
        for ($mes = 1; $mes <= 12; $mes++) {
            $ventaMes = $ventasMensuales->firstWhere('mes', $mes);
            $mesesCompletos[] = [
                'mes' => $mes,
                'total_ventas' => $ventaMes ? $ventaMes->total_ventas : 0,
                'monto_total' => $ventaMes ? (float) $ventaMes->monto_total : 0,
                'total_tomos' => $ventaMes ? $ventaMes->total_tomos : 0
            ];
        }

        return response()->json([
            'ventas_mensuales' => $mesesCompletos,
            'años_disponibles' => $añosDisponibles
        ]);
    }

    /**
     * Endpoint opcional para obtener top mangas por AJAX (si querés dejarlo)
     */
    public function getTopMangas(Request $request)
    {
        $limit = (int) $request->input('limit', 10);
        $year = $request->input('year', null);

        $query = DetalleFactura::join('facturas', 'factura_detalle.factura_id', '=', 'facturas.id')
            ->join('tomos', 'factura_detalle.tomo_id', '=', 'tomos.id')
            ->join('mangas', 'tomos.manga_id', '=', 'mangas.id')
            ->select(
                'mangas.id',
                'mangas.titulo',
                DB::raw('MAX(tomos.portada) as portada'),
                DB::raw('SUM(factura_detalle.cantidad) as total_vendido'),
                DB::raw('SUM(factura_detalle.cantidad * factura_detalle.precio_unitario) as ingresos_totales')
            )
            ->groupBy('mangas.id', 'mangas.titulo')
            ->orderByDesc('total_vendido');

        if ($year) {
            $query->whereYear('facturas.created_at', $year);
        }

        $topMangas = $query->limit($limit)->get();

        return response()->json([
            'top_mangas' => $topMangas,
            'year' => $year
        ]);
    }

    /**
     * Obtener estadísticas generales del dashboard (JSON)
     */
    public function getEstadisticasGenerales(Request $request)
    {
        $year = $request->input('year', date('Y'));

        // Estadísticas por año (evitar ambigüedades)
        $estadisticasYear = DB::table('facturas')
            ->select(
                DB::raw('COUNT(DISTINCT facturas.id) as total_ventas'),
                DB::raw('COALESCE(SUM(factura_detalle.subtotal), 0) as ingresos_totales'),
                DB::raw('COALESCE(SUM(factura_detalle.cantidad), 0) as total_tomos_vendidos'),
                DB::raw('COALESCE(AVG(factura_detalle.subtotal), 0) as promedio_venta')
            )
            ->leftJoin('factura_detalle', 'facturas.id', '=', 'factura_detalle.factura_id')
            ->whereYear('facturas.created_at', $year)
            ->first();

        // Ventas del mes actual
        $ventasMesActual = Factura::whereYear('created_at', date('Y'))
            ->whereMonth('created_at', date('m'))
            ->count();

        // Estadísticas generales (todo el tiempo)
        $estadisticasGenerales = [
            'total_mangas' => Manga::where('activo', true)->count(),
            'total_tomos' => Tomo::where('activo', true)->count(),
            'total_clientes' => Cliente::count(),
            'total_autores' => Autor::where('activo', true)->count(),
            'total_dibujantes' => Dibujante::where('activo', true)->count(),
            'total_editoriales' => Editorial::where('activo', true)->count(),
            'total_generos' => Genero::where('activo', true)->count(),
        ];

        return response()->json([
            'estadisticas_year' => [
                'total_ventas' => $estadisticasYear->total_ventas ?? 0,
                'ingresos_totales' => $estadisticasYear->ingresos_totales ?? 0,
                'total_tomos_vendidos' => $estadisticasYear->total_tomos_vendidos ?? 0,
                'promedio_venta' => $estadisticasYear->promedio_venta ?? 0,
                'ventas_mes_actual' => $ventasMesActual
            ],
            'estadisticas_generales' => $estadisticasGenerales
        ]);
    }
}

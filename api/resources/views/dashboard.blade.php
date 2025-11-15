@extends('adminlte::page')

@section('title', 'Dashboard - Panel Administrativo')

@section('content_header')
    <h1>Panel Administrativo - Mangaka Baka Shop</h1>
@stop

@section('content')
    <div class="container-fluid">
        <!-- Filtros -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="yearSelect">Seleccionar Año:</label>
                                <select id="yearSelect" class="form-control">
                                    @if(isset($añosDisponibles) && $añosDisponibles->isNotEmpty())
                                        @foreach($añosDisponibles as $a)
                                            <option value="{{ $a }}" {{ (string)$a === (string)$year ? 'selected' : '' }}>{{ $a }}</option>
                                        @endforeach
                                    @else
                                        <option value="{{ $year ?? date('Y') }}">{{ $year ?? date('Y') }}</option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="topLimit">Top Mangas a Mostrar:</label>
                                <select id="topLimit" class="form-control">
                                    @php $selectedLimit = $limit ?? 10; @endphp
                                    <option value="5"  {{ $selectedLimit == 5  ? 'selected' : '' }}>Top 5</option>
                                    <option value="10" {{ $selectedLimit == 10 ? 'selected' : '' }}>Top 10</option>
                                    <option value="15" {{ $selectedLimit == 15 ? 'selected' : '' }}>Top 15</option>
                                    <option value="20" {{ $selectedLimit == 20 ? 'selected' : '' }}>Top 20</option>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button id="btnActualizar" class="btn btn-primary">
                                    <i class="fas fa-sync-alt"></i> Actualizar Datos
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas Rápidas -->
        <div class="row mb-4">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3 id="totalVentas">0</h3>
                        <p>Ventas Totales <span id="yearTitle">{{ $year ?? '-' }}</span></p>
                    </div>
                    <div class="icon"><i class="fas fa-shopping-cart"></i></div>
            
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3 id="totalTomos">0</h3>
                        <p>Tomos Vendidos <span id="yearTitle2">{{ $year ?? '-' }}</span></p>
                    </div>
                    <div class="icon"><i class="fas fa-book"></i></div>
                    <a href="#" class="small-box-footer">Más info <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3 id="ingresosTotales">$0</h3>
                        <p>Ingresos Totales <span id="yearTitle3">{{ $year ?? '-' }}</span></p>
                    </div>
                    <div class="icon"><i class="fas fa-dollar-sign"></i></div>
                    <a href="#" class="small-box-footer">Más info <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3 id="promedioVenta">$0</h3>
                        <p>Promedio por Venta</p>
                    </div>
                    <div class="icon"><i class="fas fa-chart-line"></i></div>
                    <a href="#" class="small-box-footer">Más info <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>

        <!-- Estadísticas Generales -->
        <div class="row mb-4">
            <div class="col-md-3 col-6">
                <div class="info-box">
                    <span class="info-box-icon bg-primary"><i class="fas fa-book-open"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Mangas</span>
                        <span class="info-box-number" id="totalMangas">0</span>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-6">
                <div class="info-box">
                    <span class="info-box-icon bg-secondary"><i class="fas fa-layer-group"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Tomos</span>
                        <span class="info-box-number" id="totalTomosStock">0</span>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-6">
                <div class="info-box">
                    <span class="info-box-icon bg-success"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Clientes</span>
                        <span class="info-box-number" id="totalClientes">0</span>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-6">
                <div class="info-box">
                    <span class="info-box-icon bg-info"><i class="fas fa-shopping-bag"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Ventas Mes Actual</span>
                        <span class="info-box-number" id="ventasMesActual">0</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos Principales -->
        <div class="row">
            <!-- Ventas Mensuales -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Ventas Mensuales - <span id="yearChartTitle">{{ $year ?? '-' }}</span></h3>
                    </div>
                    <div class="card-body">
                        <canvas id="ventasMensualesChart" height="250"></canvas>
                    </div>
                </div>
            </div>

            <!-- Top Mangas (server-side render) -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Top Mangas Más Vendidos</h3></div>
                    <div class="card-body p-0">
                        <div id="topMangasList" style="max-height: 400px; overflow-y: auto;">
                            @if(isset($topMangas) && $topMangas->isNotEmpty())
                                @foreach($topMangas as $index => $manga)
                                    <div class="top-manga-item">
                                        <div class="manga-rank">{{ $index + 1 }}</div>
                                        <div class="manga-info">
                                            <div class="manga-titulo" title="{{ $manga->titulo }}">{{ $manga->titulo }}</div>
                                            <div class="manga-stats">
                                                <div><i class="fas fa-chart-bar"></i>
                                                    {{ number_format($manga->total_vendido ?? 0, 0, ',', '.') }} tomos vendidos
                                                </div>
                                                <div><i class="fas fa-dollar-sign"></i>
                                                    {{ number_format($manga->ingresos_totales ?? 0, 0, ',', '.') }} ARS
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-muted text-center p-3">No hay datos de ventas disponibles</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos Secundarios -->
        <div class="row mt-4">
            <!-- Ingresos Mensuales -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Ingresos Mensuales en Pesos Argentinos</h3></div>
                    <div class="card-body">
                        <canvas id="ingresosMensualesChart" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Tomos Vendidos por Mes -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Tomos Vendidos por Mes</h3></div>
                    <div class="card-body">
                        <canvas id="tomosMensualesChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .small-box { border-radius: .25rem; position: relative; display: block; margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,.12), 0 1px 2px rgba(0,0,0,.24); }
        .small-box>.inner { padding: 10px; }
        .small-box h3 { font-size: 2.2rem; font-weight: bold; margin: 0 0 10px 0; white-space: nowrap; padding: 0; }
        .small-box p { font-size: 1rem; }
        .top-manga-item { display:flex; align-items:center; padding:15px; border-bottom:1px solid #eee; position:relative; }
        .top-manga-item:last-child { border-bottom:none; }
        .manga-portada { width:50px; height:70px; object-fit:cover; margin-right:15px; border-radius:4px; box-shadow:0 2px 4px rgba(0,0,0,.1); }
        .manga-info { flex:1; }
        .manga-titulo { font-weight:bold; margin-bottom:5px; font-size:.9rem; line-height:1.2; }
        .manga-stats { font-size:.8rem; color:#666; }
        .manga-rank { position:absolute; left:5px; top:5px; background:#007bff; color:#fff; border-radius:50%; width:20px; height:20px; display:flex; align-items:center; justify-content:center; font-size:.7rem; font-weight:bold; }
        .info-box { box-shadow:0 1px 3px rgba(0,0,0,.12), 0 1px 2px rgba(0,0,0,.24); border-radius:.25rem; }
        .loading { opacity:.6; pointer-events:none; }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let ventasChart, ingresosChart, tomosChart;
        let currentYear = document.getElementById('yearSelect') ? document.getElementById('yearSelect').value : null;

        // Formateadores
        const formatterARS = new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', minimumFractionDigits: 0, maximumFractionDigits: 0 });
        const numberFormatter = new Intl.NumberFormat('es-AR');

        document.addEventListener('DOMContentLoaded', function() {
            configurarEventos();
            if (currentYear) cargarDatosDashboard();
        });

        function configurarEventos() {
            document.getElementById('btnActualizar').addEventListener('click', recargarConParams);
            document.getElementById('yearSelect').addEventListener('change', recargarConParams);
            document.getElementById('topLimit').addEventListener('change', recargarConParams);
        }

        function recargarConParams() {
            const y = document.getElementById('yearSelect').value;
            const l = document.getElementById('topLimit').value;
            const params = new URLSearchParams(window.location.search);
            if (y) params.set('year', y); else params.delete('year');
            if (l) params.set('limit', l); else params.delete('limit');
            window.location.search = params.toString();
        }

        async function cargarDatosDashboard() {
            if (!currentYear) currentYear = document.getElementById('yearSelect').value;
            if (!currentYear) return;

            document.body.classList.add('loading');

            try {
                await Promise.all([
                    cargarEstadisticasGenerales(),
                    cargarVentasMensuales()
                ]);
            } catch (err) {
                console.error('Error cargando datos:', err);
            } finally {
                document.body.classList.remove('loading');
            }
        }

        async function cargarEstadisticasGenerales() {
            try {
                const resp = await fetch(`/dashboard/estadisticas-generales?year=${currentYear}`);
                const data = await resp.json();

                document.getElementById('totalVentas').textContent = numberFormatter.format(data.estadisticas_year.total_ventas);
                document.getElementById('totalTomos').textContent = numberFormatter.format(data.estadisticas_year.total_tomos_vendidos);
                document.getElementById('ingresosTotales').textContent = formatterARS.format(data.estadisticas_year.ingresos_totales);
                document.getElementById('promedioVenta').textContent = formatterARS.format(data.estadisticas_year.promedio_venta);

                document.getElementById('totalMangas').textContent = numberFormatter.format(data.estadisticas_generales.total_mangas);
                document.getElementById('totalTomosStock').textContent = numberFormatter.format(data.estadisticas_generales.total_tomos);
                document.getElementById('totalClientes').textContent = numberFormatter.format(data.estadisticas_generales.total_clientes);
                document.getElementById('ventasMesActual').textContent = numberFormatter.format(data.estadisticas_year.ventas_mes_actual);
            } catch (err) {
                console.error('Error en estadísticas generales:', err);
            }
        }

        async function cargarVentasMensuales() {
            try {
                const resp = await fetch(`/dashboard/ventas-mensuales?year=${currentYear}`);
                const data = await resp.json();

                crearGraficoVentasMensuales(data.ventas_mensuales);
                crearGraficoIngresosMensuales(data.ventas_mensuales);
                crearGraficoTomosMensuales(data.ventas_mensuales);
            } catch (err) {
                console.error('Error en ventas mensuales:', err);
            }
        }

        function crearGraficoVentasMensuales(datos) {
            const ctx = document.getElementById('ventasMensualesChart').getContext('2d');
            const meses = datos.map(d => d.mes);
            const ventas = datos.map(d => d.total_ventas);

            if (ventasChart) ventasChart.destroy();

            const totalVentas = ventas.reduce((s, v) => s + v, 0);
            if (totalVentas === 0) {
                ctx.clearRect(0,0,ctx.canvas.width, ctx.canvas.height);
                ctx.font = '16px Arial';
                ctx.fillStyle = '#666';
                ctx.textAlign = 'center';
                ctx.fillText('No hay datos de ventas para este año', ctx.canvas.width / 2, ctx.canvas.height / 2);
                return;
            }

            ventasChart = new Chart(ctx, {
                type: 'bar',
                data: { labels: meses, datasets: [{ label: 'Número de Ventas', data: ventas }] },
                options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
            });
        }

        function crearGraficoIngresosMensuales(datos) {
            const ctx = document.getElementById('ingresosMensualesChart').getContext('2d');
            const meses = datos.map(d => d.mes);
            const ingresos = datos.map(d => d.monto_total);

            if (ingresosChart) ingresosChart.destroy();

            const totalIngresos = ingresos.reduce((s, v) => s + v, 0);
            if (totalIngresos === 0) {
                ctx.clearRect(0,0,ctx.canvas.width, ctx.canvas.height);
                ctx.font = '16px Arial';
                ctx.fillStyle = '#666';
                ctx.textAlign = 'center';
                ctx.fillText('No hay datos de ingresos para este año', ctx.canvas.width / 2, ctx.canvas.height / 2);
                return;
            }

            ingresosChart = new Chart(ctx, {
                type: 'line',
                data: { labels: meses, datasets: [{ label: 'Ingresos en ARS', data: ingresos, tension: 0.3, fill: true }] },
                options: {
                    responsive: true,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return formatterARS.format(context.parsed.y);
                                }
                            }
                        }
                    },
                    scales: { y: { beginAtZero: true, ticks: { callback: v => formatterARS.format(v) } } }
                }
            });
        }

        function crearGraficoTomosMensuales(datos) {
            const ctx = document.getElementById('tomosMensualesChart').getContext('2d');
            const meses = datos.map(d => d.mes);
            const tomos = datos.map(d => d.total_tomos);

            if (tomosChart) tomosChart.destroy();

            const totalTomos = tomos.reduce((s, v) => s + v, 0);
            if (totalTomos === 0) {
                ctx.clearRect(0,0,ctx.canvas.width, ctx.canvas.height);
                ctx.font = '16px Arial';
                ctx.fillStyle = '#666';
                ctx.textAlign = 'center';
                ctx.fillText('No hay datos de tomos vendidos para este año', ctx.canvas.width / 2, ctx.canvas.height / 2);
                return;
            }

            tomosChart = new Chart(ctx, {
                type: 'bar',
                data: { labels: meses, datasets: [{ label: 'Tomos Vendidos', data: tomos }] },
                options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
            });
        }
    </script>
@stop

<div class="card card-filtros p-3">
    <!-- Botón para ver stock bajo (se muestra si hay tomos con stock < 10) -->

    <h5>Filtros</h5>
    <form id="filterForm" method="GET" action="{{ route('tomos.index') }}">
        <!-- Radio buttons para elegir el tipo de filtro -->
        <div class="d-flex flex-wrap gap-3">
            <div class="form-check">
                <input class="form-check-input filter-radio" type="radio" name="filter_type" value="idioma" id="filterIdioma"
                       {{ request()->get('filter_type') == 'idioma' ? 'checked' : '' }}>
                <label class="form-check-label" for="filterIdioma">Idioma</label>
            </div>
            <div class="form-check">
                <input class="form-check-input filter-radio" type="radio" name="filter_type" value="autor" id="filterAutor"
                       {{ request()->get('filter_type') == 'autor' ? 'checked' : '' }}>
                <label class="form-check-label" for="filterAutor">Autor</label>
            </div>
            <div class="form-check">
                <input class="form-check-input filter-radio" type="radio" name="filter_type" value="manga" id="filterManga"
                       {{ request()->get('filter_type') == 'manga' ? 'checked' : '' }}>
                <label class="form-check-label" for="filterManga">Manga</label>
            </div>
            <div class="form-check">
                <input class="form-check-input filter-radio" type="radio" name="filter_type" value="editorial" id="filterEditorial"
                       {{ request()->get('filter_type') == 'editorial' ? 'checked' : '' }}>
                <label class="form-check-label" for="filterEditorial">Editorial</label>
            </div>
        </div>
        <!-- Contenedor para el select que corresponde al filtro elegido -->
        <div id="filterSelectContainer" class="mt-3">
            <select id="select-idioma" class="form-control filter-select" name="idioma">
                <option value="">Seleccione un idioma</option>
                <option value="Español" {{ request()->get('idioma') == 'Español' ? 'selected' : '' }}>Español</option>
                <option value="Inglés" {{ request()->get('idioma') == 'Inglés' ? 'selected' : '' }}>Inglés</option>
                <option value="Japonés" {{ request()->get('idioma') == 'Japonés' ? 'selected' : '' }}>Japonés</option>
            </select>
            @php
                // Extraer autores únicos de los mangas (suponiendo que cada manga tiene un autor relacionado)
                $autores = [];
                foreach($mangas as $manga){
                    if(isset($manga->autor)){
                        $autores[$manga->autor->id] = $manga->autor->nombre . ' ' . $manga->autor->apellido;
                    }
                }
            @endphp
            <select id="select-autor" class="form-control filter-select" name="autor">
                <option value="">Seleccione un autor</option>
                @foreach($autores as $id => $nombre)
                    <option value="{{ $id }}" {{ request()->get('autor') == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                @endforeach
            </select>
            <select id="select-manga" class="form-control filter-select" name="manga_id">
                <option value="">Seleccione un manga</option>
                @foreach($mangas as $manga)
                    <option value="{{ $manga->id }}" {{ request()->get('manga_id') == $manga->id ? 'selected' : '' }}>{{ $manga->titulo }}</option>
                @endforeach
            </select>
            <select id="select-editorial" class="form-control filter-select" name="editorial_id">
                <option value="">Seleccione una editorial</option>
                @foreach($editoriales as $editorial)
                    <option value="{{ $editorial->id }}" {{ request()->get('editorial_id') == $editorial->id ? 'selected' : '' }}>{{ $editorial->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </form>
    <br>
    <div class="mb-3 text-start">
        <button type="button" class="btn btn-success btn-crear-tomo" data-bs-toggle="modal" data-bs-target="#modalCrearTomo">
            Crear Tomo
        </button>
        <br>
        <br>
        @if($hasLowStock)
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#modalStock">
                Ver Stock Bajo
            </button>

        @endif
    </div>

</div>
    <!-- Mensaje cuando no se ha seleccionado ningún filtro o búsqueda -->
    @if( ! request()->has('filter_type') && ! request()->filled('search') )
        <div class="alert alert-info text-center">
            Por favor, seleccione un filtro.
        </div>
    @endif

    <div class="modal fade" id="modalStock" tabindex="-1" aria-labelledby="modalUpdateMultipleStockLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <!-- Formulario para actualizar múltiples tomos -->
                <form action="{{ route('tomos.updateMultipleStock') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="modal-title" id="modalUpdateMultipleStockLabel">Actualizar Stock de Tomos</h5>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" aria-label="Cerrar">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <div class="card-body">
                            <table id="updateStockTable" class="table table-bordered table-hover">
                                <thead>
                                    <tr>
                                        <th>Manga</th>
                                        <th>Número de Tomo</th>
                                        <th>Stock Actual</th>
                                        <th>Nuevo Stock</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($lowStockTomos as $tomo)
                                    <tr>
                                        <td>{{ $tomo->manga->titulo }}</td>
                                        <td>{{ $tomo->numero_tomo }}</td>
                                        <td>{{ $tomo->stock }}</td>
                                        <td>
                                            <!-- Enviamos el id y el nuevo stock en un array -->
                                            <input type="hidden" name="tomos[{{ $tomo->id }}][id]" value="{{ $tomo->id }}">
                                            <input type="number" name="tomos[{{ $tomo->id }}][stock]" class="form-control" value="{{ $tomo->stock }}" min="{{ $tomo->stock }}">
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer text-end">
                            <button type="submit" class="btn btn-primary">Actualizar Stock</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

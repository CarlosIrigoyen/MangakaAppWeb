<div class="card card-filtros p-3">
    <h5>Filtros</h5>
    <form id="filterForm" method="GET" action="{{ route('tomos.index') }}">
        <div class="d-flex flex-wrap gap-3">
            {{-- Idioma --}}
            <div class="form-check">
                <input class="form-check-input filter-radio" type="radio" name="filter_type" value="idioma" id="filterIdioma"
                       {{ request('filter_type') == 'idioma' ? 'checked' : '' }}>
                <label class="form-check-label" for="filterIdioma">Idioma</label>
            </div>
            {{-- Autor --}}
            <div class="form-check">
                <input class="form-check-input filter-radio" type="radio" name="filter_type" value="autor" id="filterAutor"
                       {{ request('filter_type') == 'autor' ? 'checked' : '' }}>
                <label class="form-check-label" for="filterAutor">Autor</label>
            </div>
            {{-- Manga --}}
            <div class="form-check">
                <input class="form-check-input filter-radio" type="radio" name="filter_type" value="manga" id="filterManga"
                       {{ request('filter_type') == 'manga' ? 'checked' : '' }}>
                <label class="form-check-label" for="filterManga">Manga</label>
            </div>
            {{-- Editorial --}}
            <div class="form-check">
                <input class="form-check-input filter-radio" type="radio" name="filter_type" value="editorial" id="filterEditorial"
                       {{ request('filter_type') == 'editorial' ? 'checked' : '' }}>
                <label class="form-check-label" for="filterEditorial">Editorial</label>
            </div>
            {{-- Inactivos --}}
            <div class="form-check">
                <input class="form-check-input filter-radio" type="radio" name="filter_type" value="inactivos" id="filterInactivos"
                       {{ request('filter_type') === 'inactivos' ? 'checked' : '' }}>
                <label class="form-check-label" for="filterInactivos">Inactivos</label>
            </div>
        </div>

        {{-- Aquí dejamos siempre visibles los selects, como antes --}}
        <div id="filterSelectContainer" class="mt-3">
            <select id="select-idioma" name="idioma" class="form-control filter-select" disabled>
                <option value="">Seleccione un idioma</option>
                @foreach(['Español','Inglés','Japonés'] as $lang)
                    <option value="{{ $lang }}" {{ request('idioma') == $lang ? 'selected' : '' }}>{{ $lang }}</option>
                @endforeach
            </select>

            <select id="select-autor" name="autor" class="form-control filter-select" disabled>
                <option value="">Seleccione un autor</option>
                @foreach($mangas->pluck('autor')->unique('id')->filter() as $autor)
                    <option value="{{ $autor->id }}" {{ request('autor') == $autor->id ? 'selected' : '' }}>
                        {{ $autor->nombre }} {{ $autor->apellido }}
                    </option>
                @endforeach
            </select>

            <select id="select-manga" name="manga_id" class="form-control filter-select" disabled>
                <option value="">Seleccione un manga</option>
                @foreach($mangas as $m)
                    <option value="{{ $m->id }}" {{ request('manga_id') == $m->id ? 'selected' : '' }}>
                        {{ $m->titulo }}
                    </option>
                @endforeach
            </select>

            <select id="select-editorial" name="editorial_id" class="form-control filter-select" disabled>
                <option value="">Seleccione una editorial</option>
                @foreach($editoriales as $e)
                    <option value="{{ $e->id }}" {{ request('editorial_id') == $e->id ? 'selected' : '' }}>
                        {{ $e->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </form>

    <div class="mt-4">
        <button type="button" class="btn btn-success btn-crear-tomo" data-bs-toggle="modal" data-bs-target="#modalCrearTomo">
            Crear Tomo
        </button>

        @if($hasLowStock)
            <button type="button" class="btn btn-danger ms-2" data-bs-toggle="modal" data-bs-target="#modalStock">
                Ver Stock Bajo
            </button>
        @endif
    </div>
</div>

{{-- Modal Stock (igual que antes) --}}
<div class="modal fade" id="modalStock" tabindex="-1" aria-labelledby="modalUpdateMultipleStockLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('tomos.updateMultipleStock') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="modal-title" id="modalUpdateMultipleStockLabel">Actualizar Stock de Tomos</h5>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
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
                                            <input type="hidden" name="tomos[{{ $tomo->id }}][id]" value="{{ $tomo->id }}">
                                            <input type="number" name="tomos[{{ $tomo->id }}][stock]" class="form-control"
                                                   value="{{ $tomo->stock }}" min="{{ $tomo->stock }}">
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

@section('js')
<script>
$(function(){
    // Sólo sigue habilitando/deshabilitando selects según radio,
    // idéntico a como ya lo tenías antes (sin considerar inactivos)
    $('.filter-radio').on('change', function(){
        var type = $(this).val();
        $('.filter-select').hide().prop('disabled', true);
        $('#filterSelectContainer').show();
        $('#select-'+type).show().prop('disabled', false);
    });
    var curr = "{{ request('filter_type') }}";
    if(curr){
        $('.filter-radio[value="'+curr+'"]').prop('checked', true);
        $('#filterSelectContainer').show();
        $('#select-'+curr).show().prop('disabled', false);
    }
});
</script>
@stop

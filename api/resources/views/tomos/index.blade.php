@extends('adminlte::page')

@section('title', 'Listado de Tomos')

@section('content_header')
    <h1>Listado De Tomos</h1>
@stop

@section('css')
    <!-- Archivos de estilos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.css">
    <link rel="stylesheet" href="{{ asset('css/tomos.css') }}">
      <!-- Cargar CSS de DataTables y FontAwesome -->
      <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
      <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.css">
      <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.css">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
@stop

@section('content')
<div class="container">

    <!-- Formulario de Filtros -->
    @include('partials.filtros_tomo')
    <!-- Listado de Tomos (se muestra cuando hay filtro o búsqueda) -->
    @if(request()->has('filter_type') || request()->filled('search'))
        @if($tomos->total() == 0)
            <div class="alert alert-warning text-center">
                No se encontraron tomos para los filtros o búsqueda aplicados.
            </div>
        @else
            <!-- Card contenedor para los tomos -->
            <div class="card">
                <div class="card-body g-3">
                    <div class="row" id="tomoList">
                        @foreach($tomos as $tomo)
                            <div class="col-md-4">
                                <div class="card card-tomo">
                                    <img src="{{ asset($tomo->portada) }}" alt="Portada">
                                    <div class="card-footer">
                                        <!-- Botón para abrir el modal de información -->
                                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalInfo-{{ $tomo->id }}">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                        <!-- Botón para abrir el modal de edición -->
                                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEdit-{{ $tomo->id }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <!-- Botón para abrir el modal de eliminación -->
                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalDelete-{{ $tomo->id }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <!-- Modal de Información -->
                                @include('partials.modal_info_tomo')
                                <!-- Modal de Edición -->
                                @include('partials.modal_editar_tomo')
                                <!-- Modal de Eliminación -->
                                @include('partials.modal_eliminar_tomo')

                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <!-- Paginación personalizada -->
            <div class="separator"></div>
            <div class="pagination-container">
                <button class="btn btn-light"
                        onclick="window.location.href='{{ $tomos->previousPageUrl() }}'"
                        {{ $tomos->onFirstPage() ? 'disabled' : '' }}>
                    &laquo; Anterior
                </button>
                <span id="pageIndicator">
                    Página {{ $tomos->currentPage() }} / {{ $tomos->lastPage() }}
                </span>
                <button class="btn btn-light"
                        onclick="window.location.href='{{ $tomos->nextPageUrl() }}'"
                        {{ $tomos->currentPage() == $tomos->lastPage() ? 'disabled' : '' }}>
                    Siguiente &raquo;
                </button>
            </div>
        @endif
    @endif
</div>
@include('partials.modal_crear_tomo')
@stop

@section('js')
    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function(){
            // Manejo de la visibilidad de los selects según el radio seleccionado
            $('.filter-radio').on('change', function(){
                var filterType = $(this).val();
                $('.filter-select').hide().prop('disabled', true);
                $('#filterSelectContainer').show();
                if(filterType == 'idioma'){
                    $('#select-idioma').show().prop('disabled', false);
                } else if(filterType == 'autor'){
                    $('#select-autor').show().prop('disabled', false);
                } else if(filterType == 'manga'){
                    $('#select-manga').show().prop('disabled', false);
                } else if(filterType == 'editorial'){
                    $('#select-editorial').show().prop('disabled', false);
                }
            });

            var currentFilter = "{{ request()->get('filter_type') }}";
            if(currentFilter){
                $('.filter-radio[value="'+currentFilter+'"]').prop('checked', true);
                $('#filterSelectContainer').show();
                if(currentFilter == 'idioma'){
                    $('#select-idioma').show().prop('disabled', false);
                } else if(currentFilter == 'autor'){
                    $('#select-autor').show().prop('disabled', false);
                } else if(currentFilter == 'manga'){
                    $('#select-manga').show().prop('disabled', false);
                } else if(currentFilter == 'editorial'){
                    $('#select-editorial').show().prop('disabled', false);
                }
            }

            // Variable con los datos para calcular el siguiente número de tomo y la fecha mínima (para el modal de creación).
            var nextTomos = @json($nextTomos);
            $('#manga_id').on('change', function(){
                var mangaId = $(this).val();
                var today = new Date().toISOString().split("T")[0];
                if(mangaId && nextTomos[mangaId]){
                    var data = nextTomos[mangaId];
                    $('#numero_tomo').val(data.numero);
                    if(data.numero > 1){
                        $('#fecha_publicacion').val(data.fecha);
                        $('#fecha_publicacion').attr('min', data.fecha);
                        $('#fecha_publicacion').attr('max', today);
                    } else {
                        $('#numero_tomo').val(1);
                        $('#fecha_publicacion').val('');
                        $('#fecha_publicacion').removeAttr('min');
                        $('#fecha_publicacion').attr('max', today);
                    }
                } else {
                    $('#numero_tomo').val('');
                    $('#fecha_publicacion').val('');
                    $('#fecha_publicacion').removeAttr('min');
                    $('#fecha_publicacion').attr('max', today);
                }
            });
        });
    </script>
        <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
        <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.js"></script>
        <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.js"></script>
        <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.js"></script>
@stop
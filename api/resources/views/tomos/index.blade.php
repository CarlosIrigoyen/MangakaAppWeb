@extends('adminlte::page')

@section('title', 'Listado de Tomos')

@section('css')
    <!-- Bootstrap 5 y DataTables CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.css">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="{{ asset('css/tomos.css') }}">
@stop

@section('content_header')
    <h1>Listado De Tomos</h1>
@stop

@section('content')
<div class="container">
    {{-- Filtros y botón crear --}}
    @include('partials.filtros_tomo')

    @if($tomos->total() == 0)
        <div class="alert alert-warning text-center">
            No se encontraron tomos.
        </div>
    @else
        {{-- Grid de 3 por fila --}}
        <div id="tomoList">
            @foreach($tomos as $tomo)
                <div class="card-tomo-horizontal">
                    {{-- Portada --}}
                    <div class="card-img">
                        <img src="{{ asset($tomo->portada) }}"
                             alt="Portada Tomo {{ $tomo->numero_tomo }}">
                    </div>

                    {{-- Información y botones --}}
                    <div class="card-body">
                        {{-- Título --}}
                        <h5 class="card-title">
                            {{ $tomo->manga->titulo }} — Tomo {{ $tomo->numero_tomo }}
                        </h5>

                        {{-- Datos del tomo --}}
                        <div class="card-text">
                            <p><strong>Editorial:</strong> {{ $tomo->editorial->nombre }}</p>
                            <p><strong>Formato:</strong> {{ $tomo->formato }}</p>
                            <p><strong>Idioma:</strong> {{ $tomo->idioma }}</p>
                            <p><strong>Publicación:</strong>
                                {{ \Carbon\Carbon::parse($tomo->fecha_publicacion)->format('d/m/Y') }}
                            </p>
                            <p><strong>Precio:</strong> ${{ number_format($tomo->precio, 2) }}</p>
                            <p><strong>Stock:</strong> {{ $tomo->stock }}</p>
                        </div>

                        {{-- Botones accion --}}
                        <div class="btn-group">
                            <button type="button"
                                    class="btn btn-sm btn-warning"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalEdit-{{ $tomo->id }}">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <button type="button"
                                    class="btn btn-sm btn-danger"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modalDelete-{{ $tomo->id }}">
                                <i class="fas fa-trash-alt"></i> Dar de Baja
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Modales --}}
                @include('partials.modal_info_tomo', ['tomo' => $tomo])
                @include('partials.modal_editar_tomo', [
                    'tomo' => $tomo,
                    'mangas' => $mangas,
                    'editoriales' => $editoriales
                ])
                @include('partials.modal_reactivar_tomo', ['tomo' => $tomo])

                {{-- Modal Eliminar --}}
                <div class="modal fade" id="modalDelete-{{ $tomo->id }}" tabindex="-1" aria-labelledby="modalDeleteLabel-{{ $tomo->id }}" aria-hidden="true">
                  <div class="modal-dialog">
                    <div class="modal-content">
                      <div class="modal-header">
                        <h5 class="modal-title" id="modalDeleteLabel-{{ $tomo->id }}">Dar de Baja Tomo</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                      </div>
                      <div class="modal-body">
                        ¿Estás seguro de que deseas dar de baja este tomo?
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <form action="{{ route('tomos.destroy', $tomo->id) }}" method="POST" style="display:inline;">
                          @csrf @method('DELETE')
                          <button type="submit" class="btn btn-danger">Dar de Baja</button>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>
            @endforeach
        </div>

        {{-- Paginación --}}
        <div class="separator"></div>
        <div class="pagination-container">
            <button class="btn btn-light"
                    onclick="window.location.href='{{ $tomos->previousPageUrl() }}'"
                    {{ $tomos->onFirstPage() ? 'disabled' : '' }}>
                &laquo; Anterior
            </button>
            <span> Página {{ $tomos->currentPage() }} / {{ $tomos->lastPage() }} </span>
            <button class="btn btn-light"
                    onclick="window.location.href='{{ $tomos->nextPageUrl() }}'"
                    {{ $tomos->currentPage() == $tomos->lastPage() ? 'disabled' : '' }}>
                Siguiente &raquo;
            </button>
        </div>
    @endif
</div>

{{-- Modal Crear Tomo --}}
@include('partials.modal_crear_tomo')

{{-- Modal Stock Bajo --}}
@include('partials.modal_stock_tomo')
@stop

@section('js')
    <!-- jQuery, Bootstrap y DataTables JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.min.js"></script>

    <script>
      $(document).ready(function(){
        // Filtros dinámicos
        $('.filter-radio').on('change', function(){
          var type = $(this).val();
          $('.filter-select').hide().prop('disabled', true);
          $('#filterSelectContainer').show();
          $('#select-'+type).show().prop('disabled', false);
        });
        var curr = "{{ request()->get('filter_type') }}";
        if(curr){
          $('.filter-radio[value="'+curr+'"]').prop('checked', true);
          $('#filterSelectContainer').show();
          $('#select-'+curr).show().prop('disabled', false);
        }

        // Pre-carga de datos para modal Crear Tomo
        const nextTomos = @json($nextTomos);
        function actualizarCampos() {
          var m = $('#manga_id').val();
          var e = $('#editorial_id').val();
          var info = (nextTomos[m]||{})[e] || {};

          $('#numero_tomo').val(info.numero ?? '');
          $('#precio').val(info.precio ?? '');
          if (info.formato) $('#formato').val(info.formato);
          if (info.idioma) $('#idioma').val(info.idioma);

          if (info.fechaMin) {
            $('#fecha_publicacion')
              .attr('min', info.fechaMin)
              .val(info.fechaMin);
          } else {
            $('#fecha_publicacion').removeAttr('min').val('');
          }
        }
        $('#manga_id, #editorial_id').on('change', actualizarCampos);
        $('#modalCrearTomo').on('show.bs.modal', function(){
          $('#modalCrearTomo form')[0].reset();
          $('#fecha_publicacion').removeAttr('min');
        });
      });
    </script>
@stop


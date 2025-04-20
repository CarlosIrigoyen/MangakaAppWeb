@extends('adminlte::page')

@section('title', 'Listado de Tomos')

@section('content_header')
    <h1>Listado De Tomos</h1>
@stop

@section('css')
    <!-- Estilos de Bootstrap y DataTables -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.css">
    <link rel="stylesheet" href="{{ asset('css/tomos.css') }}">
@stop

@section('content')
<div class="container">
    <!-- Filtros y botón crear -->
    @include('partials.filtros_tomo')

    @if($tomos->total() == 0)
        <div class="alert alert-warning text-center">
            No se encontraron tomos.
        </div>
    @else
        <div class="card mt-3">
            <div class="card-body">
                <div class="row" id="tomoList">
                    @foreach($tomos as $tomo)
                        <div class="col-md-4 mb-4">
                            <div class="card card-tomo">
                                <img src="{{ asset($tomo->portada) }}" class="card-img-top" alt="Portada">
                                <div class="card-footer d-flex flex-column gap-2">
                                    <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalInfo-{{ $tomo->id }}">
                                        <i class="fas fa-info-circle"></i> Información
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEdit-{{ $tomo->id }}">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <form action="{{ route('tomos.destroy', $tomo) }}" method="POST" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este tomo?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    </form>
                                </div>
                            </div>

                            @include('partials.modal_info_tomo', ['tomo' => $tomo])
                            @include('partials.modal_editar_tomo', ['tomo' => $tomo, 'mangas' => $mangas, 'editoriales' => $editoriales])
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Paginación personalizada -->
        <div class="separator"></div>
        <div class="pagination-container">
            <button class="btn btn-light" onclick="window.location.href='{{ $tomos->previousPageUrl() }}'" {{ $tomos->onFirstPage() ? 'disabled' : '' }}>&laquo; Anterior</button>
            <span> Página {{ $tomos->currentPage() }} / {{ $tomos->lastPage() }} </span>
            <button class="btn btn-light" onclick="window.location.href='{{ $tomos->nextPageUrl() }}'" {{ $tomos->currentPage() == $tomos->lastPage() ? 'disabled' : '' }}>Siguiente &raquo;</button>
        </div>
    @endif
</div>

<!-- Modal: Crear Tomo -->
<div class="modal fade" id="modalCrearTomo" tabindex="-1" aria-labelledby="modalCrearTomoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalCrearTomoLabel">Crear Tomo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        @if ($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <form action="{{ route('tomos.store') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <input type="hidden" name="redirect_to" value="{{ url()->full() }}">

          <div class="mb-3">
            <label for="manga_id" class="form-label">Manga</label>
            <select id="manga_id" name="manga_id" class="form-select" required>
              <option value="">Seleccione un manga</option>
              @foreach($mangas as $m)
                <option value="{{ $m->id }}">{{ $m->titulo }}</option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label for="editorial_id" class="form-label">Editorial</label>
            <select id="editorial_id" name="editorial_id" class="form-select" required>
              <option value="">Seleccione una editorial</option>
              @foreach($editoriales as $e)
                <option value="{{ $e->id }}">{{ $e->nombre }}</option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label for="formato" class="form-label">Formato</label>
            <select id="formato" name="formato" class="form-select" required>
              <option value="">Seleccione un formato</option>
              @foreach(['Tankōbon','Aizōban','Kanzenban','Bunkoban','Wideban'] as $fmt)
                <option value="{{ $fmt }}">{{ $fmt }}</option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label for="idioma" class="form-label">Idioma</label>
            <select id="idioma" name="idioma" class="form-select" required>
              <option value="">Seleccione un idioma</option>
              @foreach(['Español','Inglés','Japonés'] as $lang)
                <option value="{{ $lang }}">{{ $lang }}</option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label for="numero_tomo" class="form-label">Número de Tomo</label>
            <input type="number" id="numero_tomo" name="numero_tomo" class="form-control">
          </div>

          <div class="mb-3">
            <label for="fecha_publicacion" class="form-label">Fecha de Publicación</label>
            <input type="date" id="fecha_publicacion" name="fecha_publicacion" class="form-control" required>
          </div>

          <div class="mb-3">
            <label for="precio" class="form-label">Precio</label>
            <input type="number" step="0.01" id="precio" name="precio" class="form-control" required>
          </div>

          <div class="mb-3">
            <label for="stock" class="form-label">Stock</label>
            <input type="number" id="stock" name="stock" class="form-control" min="0" value="0" required>
          </div>

          <div class="mb-3">
            <label for="portada" class="form-label">Portada</label>
            <input type="file" id="portada" name="portada" class="form-control" required>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Crear Tomo</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@stop

@section('js')
    <!-- Scripts de Bootstrap y DataTables -->
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

        // Pre-carga de datos editables
        const nextTomos = @json($nextTomos);
        function actualizarCampos() {
          var m = $('#manga_id').val();
          var e = $('#editorial_id').val();
          var info = (nextTomos[m]||{})[e] || {};

          $('#numero_tomo').val(info.numero ?? '');
          $('#precio').val(info.precio ?? '');
          if (info.formato) $('#formato').val(info.formato);
          if (info.idioma) $('#idioma').val(info.idioma);

          if(info.fechaMin) {
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

@extends('adminlte::page')

@section('title', 'Listado de Mangas')

@section('content_header')
    <h1>Listado de Mangas</h1>
@stop

@section('css')
    <style>
        .acciones-container { display: flex; gap: 8px; justify-content: center; }
        #Contenido { visibility: hidden; }
    </style>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
@stop

@section('content')
    @php $status = $status ?? request('status', 'activo'); @endphp

    <div class="container">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          {{-- Toggle Activos / Inactivos --}}
          <div>
            <a href="{{ route('mangas.index',['status'=>'activo']) }}"
               class="btn btn-{{ $status==='activo' ? 'primary':'outline-primary' }}">
              Activos
            </a>
            <a href="{{ route('mangas.index',['status'=>'inactivo']) }}"
               class="btn btn-{{ $status==='inactivo' ? 'primary':'outline-primary' }}">
              Inactivos
            </a>
          </div>
          {{-- “Crear” solo en activos --}}
          @if($status==='activo')
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCrear">
              Crear Manga
            </button>
          @endif
        </div>

        <div class="card-body">
          <table id="Contenido"
                 class="table table-bordered table-hover dataTable nowrap"
                 style="width:100%">
            <thead>
              <tr>
                <th>#</th><th>Nombre</th><th>Autor</th><th>Dibujante</th>
                <th>Géneros</th><th>En Publicación</th><th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              @foreach($mangas as $m)
                <tr>
                  <td>{{ $m->id }}</td>
                  <td>{{ $m->titulo }}</td>
                  <td>{{ $m->autor->nombre }} {{ $m->autor->apellido }}</td>
                  <td>{{ $m->dibujante->nombre }} {{ $m->dibujante->apellido }}</td>
                  <td>
                    @foreach($m->generos as $g)
                      {{ $g->nombre }}@if(!$loop->last), @endif
                    @endforeach
                  </td>
                  <td class="text-center">{{ $m->en_publicacion==='si'?'SI':'NO' }}</td>
                  <td class="text-center">
                    <div class="acciones-container">
                      @if($status==='activo')
                        <button class="btn btn-sm btn-warning"
                                data-bs-toggle="modal" data-bs-target="#modalEditar"
                                onclick="editarManga({{ json_encode($m) }})">
                          <i class="fas fa-pen"></i>
                        </button>
                        <button class="btn btn-sm btn-danger"
                                data-bs-toggle="modal" data-bs-target="#modalEliminar"
                                onclick="configurarEliminar({{ $m->id }})">
                          <i class="fas fa-trash-alt"></i>
                        </button>
                      @else
                        <form action="{{ route('mangas.reactivate',$m->id) }}"
                              method="POST" style="display:inline">
                          @csrf
                          <button class="btn btn-sm btn-success">
                            <i class="fas fa-redo"></i>
                          </button>
                        </form>
                      @endif
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    @include('partials.modal_crear_manga')
    @include('partials.modal_editar_manga')
    @include('partials.modal_eliminar_manga')
@stop

@section('js')
  <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
  <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.js"></script>
  <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.js"></script>
  <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.js"></script>
  <script src="{{ asset('js/mangas.js') }}"></script>
@stop

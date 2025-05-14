@extends('adminlte::page')

@section('title', 'Listado de Editoriales')

@section('content_header')
    <h1>Listado de Editoriales</h1>
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        #Contenido { visibility: hidden; }
        .acciones-container {
            display: flex;
            gap: 10px;
            justify-content: center;
            align-items: center;
        }
    </style>
@stop

@section('content')
    @php $status = $status ?? request('status', 'activo'); @endphp

    <div class="container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                {{-- Toggle Act/Inact --}}
                <div>
                    <a href="{{ route('editoriales.index', ['status' => 'activo']) }}"
                       class="btn btn-{{ $status==='activo' ? 'primary' : 'outline-primary' }}">
                        Activas
                    </a>
                    <a href="{{ route('editoriales.index', ['status' => 'inactivo']) }}"
                       class="btn btn-{{ $status==='inactivo' ? 'primary' : 'outline-primary' }}">
                        Inactivas
                    </a>
                </div>
                {{-- Solo “Crear” en activas --}}
                @if($status==='activo')
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCrear">
                        Crear Editorial
                    </button>
                @endif
            </div>
            <div class="card-body table-responsive">
                <table id="Contenido" class="table table-bordered table-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>País</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($editoriales as $e)
                            <tr>
                                <td>{{ $e->id }}</td>
                                <td>{{ $e->nombre }}</td>
                                <td>{{ $e->pais }}</td>
                                <td class="text-center">
                                    <div class="acciones-container">
                                        @if($status==='activo')
                                            {{-- Editar --}}
                                            <button class="btn btn-sm btn-warning"
                                                    data-bs-toggle="modal" data-bs-target="#modalEditar"
                                                    onclick="editarEditorial({{ $e->id }})">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            {{-- Inactivar --}}
                                            <button class="btn btn-sm btn-danger"
                                                    data-bs-toggle="modal" data-bs-target="#modalEliminar"
                                                    onclick="configurarEliminar({{ $e->id }})">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        @else
                                            {{-- Reactivar --}}
                                            <form action="{{ route('editoriales.reactivate', $e->id) }}"
                                                  method="POST" style="display:inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">
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

        @include('partials.modal_crear_editorial')
        @include('partials.modal_editar_editorial')
        @include('partials.modal_eliminar_editorial')
    </div>
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.js"></script>
    <script src="{{ asset('js/editorial.js') }}"></script>
@stop

@extends('adminlte::page')

@section('title', 'Listado de Géneros')

@section('content_header')
    <h1>Listado de Géneros</h1>
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
                {{-- Toggle Activos / Inactivos --}}
                <div>
                    <a href="{{ route('generos.index', ['status' => 'activo']) }}"
                       class="btn btn-{{ $status==='activo' ? 'primary' : 'outline-primary' }}">
                        Activos
                    </a>
                    <a href="{{ route('generos.index', ['status' => 'inactivo']) }}"
                       class="btn btn-{{ $status==='inactivo' ? 'primary' : 'outline-primary' }}">
                        Inactivos
                    </a>
                </div>
                {{-- “Crear Género” solo en Activos --}}
                @if($status === 'activo')
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCrear">
                        Crear Género
                    </button>
                @endif
            </div>
            <div class="card-body table-responsive">
                <table id="Contenido"
                       class="table table-bordered table-hover dataTable dtr-inline"
                       style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th style="width:80px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($generos as $genero)
                            <tr>
                                <td>{{ $genero->id }}</td>
                                <td>{{ $genero->nombre }}</td>
                                <td class="text-center">
                                    <div class="acciones-container">
                                        @if($status === 'activo')
                                            {{-- Editar --}}
                                            <button class="btn btn-sm btn-warning"
                                                    data-bs-toggle="modal" data-bs-target="#modalEditar"
                                                    onclick="editarGenero({{ $genero->id }})">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            {{-- Inactivar --}}
                                            <button class="btn btn-sm btn-danger"
                                                    data-bs-toggle="modal" data-bs-target="#modalEliminar"
                                                    onclick="configurarEliminar({{ $genero->id }})">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        @else
                                            {{-- Reactivar --}}
                                            <form action="{{ route('generos.reactivate', $genero->id) }}"
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

        @include('partials.modal_crear_genero')
        @include('partials.modal_editar_genero')
        @include('partials.modal_eliminar_genero')
    </div>
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.js"></script>
    <script src="{{ asset('js/genero.js') }}"></script>
@stop

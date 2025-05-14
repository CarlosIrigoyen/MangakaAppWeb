@extends('adminlte::page')

@section('title', 'Listado de Dibujantes')

@section('content_header')
    <h1>Listado de Dibujantes</h1>
@stop

@section('css')
    <!-- DataTables & FontAwesome -->
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
    @php
        $status = $status ?? request('status', 'activo');
    @endphp

    <div class="container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                {{-- Toggle Activos/Inactivos --}}
                <div>
                    <a href="{{ route('dibujantes.index', ['status' => 'activo']) }}"
                       class="btn btn-{{ $status === 'activo' ? 'primary' : 'outline-primary' }}">
                        Activos
                    </a>
                    <a href="{{ route('dibujantes.index', ['status' => 'inactivo']) }}"
                       class="btn btn-{{ $status === 'inactivo' ? 'primary' : 'outline-primary' }}">
                        Inactivos
                    </a>
                </div>
                {{-- Botón “Crear” solo en activos --}}
                @if($status === 'activo')
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCrear">
                        Crear Dibujante
                    </button>
                @endif
            </div>

            <div class="card-body">
                <table id="Contenido" class="table table-bordered table-hover dataTable dtr-inline">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Apellido</th>
                            <th>Fecha de Nacimiento</th>
                            <th style="width: 80px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dibujantes as $dibujante)
                            <tr>
                                <td>{{ $dibujante->id }}</td>
                                <td>{{ $dibujante->nombre }}</td>
                                <td>{{ $dibujante->apellido }}</td>
                                <td>{{ \Carbon\Carbon::parse($dibujante->fecha_nacimiento)->format('d/m/Y') }}</td>
                                <td class="text-center">
                                    <div class="acciones-container">
                                        @if($status === 'activo')
                                            {{-- Editar --}}
                                            <button type="button" class="btn btn-sm btn-warning"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalEditar"
                                                    onclick="editarDibujante({{ $dibujante->id }})">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            {{-- Inactivar --}}
                                            <button type="button" class="btn btn-sm btn-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalEliminar"
                                                    onclick="configurarEliminar({{ $dibujante->id }})">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        @else
                                            {{-- Reactivar --}}
                                            <form action="{{ route('dibujantes.reactivate', $dibujante->id) }}"
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

        @include('partials.modal_crear_dibujante')
        @include('partials.modal_editar_dibujante')
        @include('partials.modal_eliminar_dibujante')
    </div>
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.js"></script>
    <script src="{{ asset('js/dibujantes.js') }}"></script>
@stop

@extends('adminlte::page')

@section('title', 'Listado de Géneros')

@section('content_header')
    <h1>Listado de Géneros</h1>
@stop

@section('css')
    <!-- Cargar CSS de DataTables, Bootstrap y FontAwesome -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        /* Evitar parpadeos en la tabla */
        #Contenido {
            visibility: hidden;
        }

        /* Contenedor flex para los botones de acción */
        .acciones-container {
            display: flex;
            gap: 10px; /* Espacio entre botones */
            justify-content: center;
            align-items: center;
        }
    </style>
@stop

@section('content')
    <div class="container">
        <!-- Card con la tabla de géneros -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <!-- Botón para crear género -->
                <button type="button"
                        class="btn btn-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#modalCrear">
                    Crear Género/s
                </button>
            </div>
            <div class="card-body table-responsive">
                <!-- Tabla con id "Contenido" para DataTables -->
                <table id="Contenido"
                       class="table table-bordered table-hover dataTable dtr-inline"
                       style="width:100%">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th style="width: 80px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($generos as $genero)
                            <tr>
                                <td>{{ $genero->id }}</td>
                                <td>{{ $genero->nombre }}</td>
                                <td class="text-center">
                                    <div class="acciones-container">
                                        <!-- Editar -->
                                        <button type="button"
                                                class="btn btn-sm btn-warning"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEditar"
                                                onclick="editarGenero({{ $genero->id }})">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <!-- Eliminar -->
                                        <button type="button"
                                                class="btn btn-sm btn-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEliminar"
                                                onclick="configurarEliminar({{ $genero->id }})">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
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
    <!-- Scripts de jQuery, Bootstrap y DataTables -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.js"></script>

    <!-- Toda la inicialización de la tabla se realiza en js/genero.js -->
    <script src="{{ asset('js/genero.js') }}"></script>
@stop

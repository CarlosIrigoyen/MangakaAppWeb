@extends('adminlte::page')

@section('title', 'Listado de Autores')

@section('content_header')
    <h1>Listado de Autores</h1>
@stop

@section('css')
    <style>
        /* Contenedor flex para los botones de acción */
        .acciones-container {
            display: flex;
            gap: 10px;
            justify-content: center;
            align-items: center;
        }

        /* Evitar parpadeo al cargar */
        #Contenido {
            visibility: hidden;
        }
    </style>

    <!-- CSS de DataTables y FontAwesome -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
@stop

@section('content')
    <div class="container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrear">
                    Crear Autor
                </button>
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
                        @foreach($autores as $autor)
                            <tr>
                                <td>{{ $autor->id }}</td>
                                <td>{{ $autor->nombre }}</td>
                                <td>{{ $autor->apellido }}</td>
                                <td>{{ \Carbon\Carbon::parse($autor->fecha_nacimiento)->format('d/m/Y') }}</td>
                                <td class="text-center">
                                    <div class="acciones-container">
                                        <button type="button" class="btn btn-sm btn-warning"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEditar"
                                                onclick="editarAutor({{ $autor->id }})">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEliminar"
                                                onclick="configurarEliminar({{ $autor->id }})">
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
    </div>

    @include('partials.modal_crear_autor')
    @include('partials.modal_editar_autor')
    @include('partials.modal_eliminar_autor')
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.js"></script>
    <script>
        $(document).ready(function() {
            let table = $('#Contenido').DataTable({
                responsive: true,
                autoWidth: false,
                language: {
                    lengthMenu: "Mostrar _MENU_ registros por página",
                    zeroRecords: "No se encontraron resultados",
                    info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    infoEmpty: "Mostrando 0 a 0 de 0 registros",
                    infoFiltered: "(filtrado de _MAX_ registros totales)",
                    search: "Buscar:",
                    emptyTable: "No se encontraron autores"
                },
                initComplete: function () {
                    $('#Contenido').css('visibility', 'visible');
                }
            });

            $(window).on('orientationchange resize', function() {
                table.columns.adjust().responsive.recalc();
            });

            $('#modalEditar, #modalCrear, #modalEliminar').on('shown.bs.modal', function () {
                table.columns.adjust().responsive.recalc();
            });
        });
    </script>
    <script src="{{ asset('js/autor.js') }}"></script>
@stop

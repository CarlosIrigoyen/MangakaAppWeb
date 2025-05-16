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
    @php
        // Recibido desde el controlador: 'activo' o 'inactivo'
        $status = $status ?? request('status', 'activo');
    @endphp

    <div class="container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                {{-- Toggle Activos / Inactivos --}}
                <div>
                    <a href="{{ route('autores.index', ['status' => 'activo']) }}"
                       class="btn btn-{{ $status === 'activo' ? 'primary' : 'outline-primary' }}">
                        Activos
                    </a>
                    <a href="{{ route('autores.index', ['status' => 'inactivo']) }}"
                       class="btn btn-{{ $status === 'inactivo' ? 'primary' : 'outline-primary' }}">
                        Inactivos
                    </a>
                </div>

                {{-- Botón "Crear Autor" solo en Activos --}}
                @if($status === 'activo')
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCrear">
                        Crear Autor
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
                        @foreach($autores as $autor)
                            <tr>
                                <td>{{ $autor->id }}</td>
                                <td>{{ $autor->nombre }}</td>
                                <td>{{ $autor->apellido }}</td>
                                <td>{{ \Carbon\Carbon::parse($autor->fecha_nacimiento)->format('d/m/Y') }}</td>
                                <td class="text-center">
                                    <div class="acciones-container">
                                        @if($status === 'activo')
                                            {{-- Editar --}}
                                            <button type="button" class="btn btn-sm btn-warning"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalEditar"
                                                    onclick="editarAutor({{ $autor->id }})">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            {{-- Inactivar (eliminar) --}}
                                            <button type="button" class="btn btn-sm btn-danger"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modalEliminar"
                                                    onclick="configurarEliminar({{ $autor->id }})">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        @else
                                            {{-- Reactivar con modal reutilizable --}}
                                            <form action="{{ route('autores.reactivate', $autor->id) }}"
                                                  method="POST"
                                                  class="reactivar-form"
                                                  data-confirm="¿Deseas reactivar este autor?"
                                                  style="display:inline">
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

        {{-- Partials de modales --}}
        @include('partials.modal_crear_autor')
        @include('partials.modal_editar_autor')
        @include('partials.modal_eliminar_autor')

        {{-- Modal de confirmación reutilizable --}}
        <div class="modal fade" id="modalConfirmacion" tabindex="-1" aria-labelledby="modalConfirmacionLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalConfirmacionLabel">Confirmar Acción</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <p id="mensajeConfirmacion">¿Estás seguro de realizar esta acción?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-danger" id="btnConfirmarAccion">Confirmar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.js"></script>
    <script src="{{ asset('js/autor.js') }}"></script>
    <!-- Script confirmación genérico -->
    <script src="{{ asset('js/confirmacion.js') }}"></script>
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

        // Intercepta cualquier formulario con data-confirm para usar el modal
        let formToSubmit = null;
        $(document).on('submit', 'form[data-confirm]', function(e) {
            e.preventDefault();
            formToSubmit = this;
            const mensaje = $(this).data('confirm');
            $('#mensajeConfirmacion').text(mensaje);

            const $btn = $('#btnConfirmarAccion');
            if ($(this).hasClass('reactivar-form')) {
                $btn.removeClass('btn-danger').addClass('btn-success').text('Reactivar');
            } else {
                $btn.removeClass('btn-success').addClass('btn-danger').text('Confirmar');
            }

            $('#modalConfirmacion').modal('show');
        });

        // Al confirmar en el modal, envía el formulario guardado
        $('#btnConfirmarAccion').on('click', function() {
            if (formToSubmit) {
                formToSubmit.submit();
            }
        });
    </script>
@stop

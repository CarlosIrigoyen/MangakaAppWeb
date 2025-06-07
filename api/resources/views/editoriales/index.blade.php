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
                                            <button
                                              class="btn btn-sm btn-warning"
                                              onclick="editarEditorial({{ $e->id }})"
                                            >
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <button
                                              class="btn btn-sm btn-danger"
                                              data-bs-toggle="modal"
                                              data-bs-target="#modalEliminar"
                                              onclick="configurarEliminar({{ $e->id }})"
                                            >
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        @else
                                            <form action="{{ route('editoriales.reactivate', $e->id) }}"
                                                  method="POST"
                                                  class="reactivar-form"
                                                  data-confirm="¿Deseas reactivar esta editorial?"
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

        @include('partials.modal_crear_editorial')
        @include('partials.modal_eliminar_editorial')
        @include('partials.modal_editar_editorial')
        {{-- Modal de confirmación genérico --}}
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
    <script src="{{ asset('js/confirmacion.js') }}"></script>

    <script>
        $(document).ready(function() {
            let table;
            if (!$.fn.DataTable.isDataTable('#Contenido')) {
                table = $('#Contenido').DataTable({
                    responsive: true,
                    autoWidth: false,
                    language: {
                        lengthMenu: "Mostrar _MENU_ registros por página",
                        zeroRecords: "No se encontraron resultados",
                        info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
                        infoEmpty: "Mostrando 0 a 0 de 0 registros",
                        infoFiltered: "(filtrado de _MAX_ registros totales)",
                        search: "Buscar:",
                        emptyTable: "No se encontraron editoriales"
                    },
                    initComplete: function () {
                        $('#Contenido').css('visibility', 'visible');
                    }
                });
            } else {
                table = $('#Contenido').DataTable();
            }

            $(window).on('orientationchange resize', function() {
                table.columns.adjust().responsive.recalc();
            });

            $('#modalCrear, #modalEliminar').on('shown.bs.modal', function () {
                table.columns.adjust().responsive.recalc();
            });

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

            $('#btnConfirmarAccion').on('click', function() {
                if (formToSubmit) {
                    formToSubmit.submit();
                }
            });
        });

        function editarEditorial(id) {
            console.log('>>> editarEditorial() invocado con ID =', id);

            $.ajax({
                url: '/editoriales/' + id + '/edit',
                method: 'GET',
                success: function(data) {
                    console.log(data.nombre);
                    $('#nombre_edicion').val(data.nombre);
                    $('#pais_edicion').val(data.pais);
                    $('#formEditar').attr('action', '/editoriales/' + id);
                    $('#modalEditar').modal('show');
                },
                error: function(xhr, status, error) {
                    console.error("Error al cargar los datos de la editorial:", error);
                }
            });
        }

        function configurarEliminar(id) {
            $('#formEliminar').attr('action', '/editoriales/' + id);
            $('#eliminar-body-text')
              .text('¿Estás seguro de que deseas dar de baja esta editorial?');
            $('#btnConfirmEliminar')
              .prop('disabled', false)
              .text('Eliminar');

            $.ajax({
                url: '/editoriales/' + id + '/check-tomos',
                method: 'GET',
                success: function(data) {
                    if (data.tomos_count > 0) {
                        $('#eliminar-body-text').html(
                            'La editorial <strong>' + data.nombre + '</strong> tiene ' +
                            data.tomos_count + ' tomo(s) asociados y no se puede dar de baja.'
                        );
                        $('#btnConfirmEliminar')
                          .prop('disabled', true)
                          .text('No se puede dar de baja');
                    }
                },
                error: function() {
                    $('#eliminar-body-text').text('Error al comprobar dependencias.');
                    $('#btnConfirmEliminar').prop('disabled', true);
                }
            });
        }
    </script>
@stop

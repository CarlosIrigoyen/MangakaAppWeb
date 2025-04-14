@extends('adminlte::page')

@section('title', 'Listado de Mangas')

@section('content_header')
    <h1>Listado de Mangas</h1>
@stop

@section('css')
    <style>
        /* Contenedor flex para los botones de acción */
        .acciones-container {
            display: flex;
            gap: 10px; /* Espacio entre botones */
            justify-content: center;
            align-items: center;
        }
        /* Evitar parpadeos en la tabla */
        #Contenido {
            visibility: hidden;
        }
    </style>

    <!-- Cargar CSS de DataTables y FontAwesome -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
@stop

@section('content')
    <div class="container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <!-- Botón para crear manga -->
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrear">
                    Crear Manga
                </button>
            </div>
            <div class="card-body">
                <!-- Tabla para listar mangas -->
                <table id="Contenido" class="table table-bordered table-hover dataTable dtr-inline">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Autor</th>
                            <th>Dibujante</th>
                            <th>Género</th>
                            <th>En Publicación</th>
                            <th style="width: 80px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($mangas as $manga)
                            <tr>
                                <td>{{ $manga->id }}</td>
                                <td>{{ $manga->titulo }}</td>
                                <td>{{ $manga->autor->nombre }} {{ $manga->autor->apellido }}</td>
                                <td>{{ $manga->dibujante->nombre }} {{ $manga->dibujante->apellido }}</td>
                                <td>
                                    @foreach($manga->generos as $genero)
                                        {{ $genero->nombre }}@if(!$loop->last), @endif
                                    @endforeach
                                </td>
                                <td>{{ $manga->en_publicacion ? 'Sí' : 'No' }}</td>
                                <td class="text-center">
                                    <div class="acciones-container">
                                        <!-- Botón para editar manga -->
                                        <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEditar"
                                            onclick="editarManga({{ json_encode($manga) }})">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <!-- Botón para eliminar manga -->
                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalEliminar"
                                            onclick="configurarEliminar({{ $manga->id }})">
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
    <script>
        $(document).ready(function() {
            $('#Contenido').DataTable({
                responsive: true,
                order: [[0, 'desc']],
                "language": {
                    "lengthMenu": "Mostrar _MENU_ registros por página",
                    "zeroRecords": "No se encontraron resultados",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                    "infoFiltered": "(filtrado de _MAX_ registros totales)",
                    "search": "Buscar:"
                }
            });
            $('#Contenido').css('visibility', 'visible');
        });

        function editarManga(manga) {
            // Rellenar el formulario de edición con los datos del manga
            $('#manga_id').val(manga.id);
            $('#titulo_editar').val(manga.titulo);
            $('#autor_editar').val(manga.autor.id);
            $('#dibujante_editar').val(manga.dibujante.id);

            // Limpiar previamente las casillas de géneros y marcarlas según el manga
            $('.genero-checkbox').prop('checked', false);
            manga.generos.forEach(function(genero) {
                $('#genero_editar' + genero.id).prop('checked', true);
            });

            // Establecer el valor del checkbox de publicación
            $('#en_publicacion_editar').prop('checked', manga.en_publicacion);

            // Configurar la acción del formulario para actualizar el manga
            $('#formEditar').attr('action', '/mangas/' + manga.id);
        }

        function configurarEliminar(mangaId) {
            $('#formEliminar').attr('action', '/mangas/' + mangaId);
        }
    </script>
@stop

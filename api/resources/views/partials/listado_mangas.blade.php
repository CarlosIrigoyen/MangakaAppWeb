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
                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEditar" onclick="editarManga(@json($manga))">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <!-- Botón para eliminar manga -->
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalEliminar" onclick="configurarEliminar({{ $manga->id }})">
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
    <!-- Contenedor con el Card de Bootstrap -->
    <div class="container">
        <!-- Card con la tabla de autores -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <!-- Botón para crear autor -->
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrear">
                    Crear Autor
                </button>
            </div>
            <div class="card-body">
                <!-- Tabla con ID para aplicar DataTables -->
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
                                    <!-- Botón para editar autor -->
                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEditar" onclick="editarAutor({{ $autor->id }})">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <span class="mx-2"></span>
                                    <!-- Botón para eliminar autor -->
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalEliminar" onclick="configurarEliminar({{ $autor->id }})">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
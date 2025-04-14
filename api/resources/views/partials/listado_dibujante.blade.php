        <!-- Card con la tabla de dibujantes -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <!-- Botón para crear dibujante -->
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrear">
                    Crear Dibujante
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
                            <th style="width: 80px;">Acciones</th> <!-- Ajusta el tamaño de la columna de acciones -->
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dibujantes as $dibujante)
                            <tr>
                                <td>{{ $dibujante->id }}</td>
                                <td>{{ $dibujante->nombre }}</td>
                                <td>{{ $dibujante->apellido }}</td>
                                <td class="text-center">
                                    <!-- Icono para editar dibujante -->
                                    <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEditar" onclick="editarDibujante({{ $dibujante->id }})">
                                        <i class="fas fa-pen"></i> <!-- Icono de pluma -->
                                    </button>
                                    <!-- Espacio entre los iconos -->
                                    <span class="mx-2"></span>
                                    <!-- Icono para eliminar dibujante -->
                                    <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalEliminar" onclick="configurarEliminar({{ $dibujante->id }})">
                                        <i class="fas fa-trash-alt"></i> <!-- Icono de tacho -->
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
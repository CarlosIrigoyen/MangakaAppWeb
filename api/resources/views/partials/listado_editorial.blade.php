<!-- Contenedor con el Card de Bootstrap -->
<div class="container">
    <!-- Card con la tabla de editoriales -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <!-- Botón para crear editorial -->
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrear">
                Crear Editorial
            </button>
        </div>
        <div class="card-body table-responsive">
            <!-- Tabla con id "editorialesTable" para aplicar DataTables -->
            <table id="editorialesTable" class="table table-bordered table-hover dataTable dtr-inline" style="width: 100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>País</th>
                        <th style="width: 80px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($editoriales as $editorial)
                        <tr>
                            <td>{{ $editorial->id }}</td>
                            <td>{{ $editorial->nombre }}</td>
                            <td>{{ $editorial->pais }}</td>
                            <td class="text-center">
                                <!-- Botón para editar editorial -->
                                <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#modalEditar" onclick="editarEditorial({{ $editorial->id }})">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <!-- Espacio entre iconos -->
                                <span class="mx-2"></span>
                                <!-- Botón para eliminar editorial -->
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalEliminar" onclick="configurarEliminar({{ $editorial->id }})">
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
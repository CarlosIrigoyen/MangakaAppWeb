    <!-- Modal para editar dibujante -->
    <div class="modal fade" id="modalEditar" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEditarLabel">Editar Dibujante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Formulario de edición -->
                    <form id="formEditar" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="nombre_edit" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre_edit" name="nombre" required>
                            <span id="nombreErrorEdit" class="text-danger"></span>
                        </div>
                        <div class="mb-3">
                            <label for="apellido_edit" class="form-label">Apellido</label>
                            <input type="text" class="form-control" id="apellido_edit" name="apellido" required>
                            <span id="apellidoErrorEdit" class="text-danger"></span>
                        </div>
                        <div class="mb-3">
                            <label for="fecha_nacimiento_edit" class="form-label">Fecha de Nacimiento</label>
                            <!-- Se establece el atributo max para permitir solo fechas válidas -->
                            <input type="date" class="form-control" id="fecha_nacimiento_edit" name="fecha_nacimiento" required max="{{ \Carbon\Carbon::now()->subYears(18)->toDateString() }}">
                            <span id="fechaErrorEdit" class="text-danger"></span>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <!-- Botón de edición, inicialmente deshabilitado hasta validación -->
                            <button type="submit" class="btn btn-primary" id="editButton" disabled>Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

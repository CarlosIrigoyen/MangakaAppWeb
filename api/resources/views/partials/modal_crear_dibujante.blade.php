    <!-- Modal para crear dibujante -->
    <div class="modal fade" id="modalCrear" tabindex="-1" aria-labelledby="modalCrearLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCrearLabel">Crear Dibujante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Formulario para crear un nuevo dibujante -->
                    <form id="formCrear" action="{{ route('dibujantes.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                            <span id="nombreError" class="text-danger">
                                @error('nombre') {{ $message }} @enderror
                            </span>
                        </div>
                        <div class="mb-3">
                            <label for="apellido" class="form-label">Apellido</label>
                            <input type="text" class="form-control" id="apellido" name="apellido" required>
                            <span id="apellidoError" class="text-danger">
                                @error('apellido') {{ $message }} @enderror
                            </span>
                        </div>
                        <div class="mb-3">
                            <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                            <!-- Se establece el atributo max para restringir la selección -->
                            <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" required max="{{ \Carbon\Carbon::now()->subYears(18)->toDateString() }}">
                            <span id="fechaError" class="text-danger">
                                @error('fecha_nacimiento') {{ $message }} @enderror
                            </span>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <!-- Botón deshabilitado hasta cumplir validaciones -->
                            <button type="submit" class="btn btn-primary" id="crearButton" disabled>Crear Dibujante</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
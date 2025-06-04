{{-- Modal de edición con IDs modificados --}}
        <div class="modal fade" id="modalEditar" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEditarLabel">Editar Editorial</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formEditar" method="POST">
                            @csrf
                            @method('PUT')

                            <!-- Campo Nombre ahora con id="nombre_edicion" -->
                            <div class="mb-3">
                                <label for="nombre_edicion" class="form-label">Nombre</label>
                                <input
                                  type="text"
                                  class="form-control"
                                  id="nombre_edicion"
                                  name="nombre"
                                  required
                                >
                            </div>

                            <!-- Campo País ahora con id="pais_edicion" -->
                            <div class="mb-3">
                                <label for="pais_edicion" class="form-label">País</label>
                                <select
                                  class="form-control"
                                  id="pais_edicion"
                                  name="pais"
                                  required
                                >
                                    <option value="" disabled selected>— Selecciona un país —</option>
                                    @foreach($paises as $p)
                                        <option value="{{ $p }}">{{ $p }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="modal-footer">
                                <button
                                  type="button"
                                  class="btn btn-secondary"
                                  data-bs-dismiss="modal"
                                >Cancelar</button>
                                <button
                                  type="submit"
                                  class="btn btn-primary"
                                >Guardar Cambios</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

<!-- Modal para editar manga -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-labelledby="modalEditarLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarLabel">Editar Manga</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formEditar" action="" method="POST">
                    @csrf
                    @method('PUT')
                    <!-- Campo oculto para el id del manga -->
                    <input type="hidden" id="manga_id" name="id">
                    <div class="mb-3">
                        <label for="titulo_editar" class="form-label">Título</label>
                        <input type="text" class="form-control" id="titulo_editar" name="titulo" required>
                    </div>
                    <div class="mb-3">
                        <label for="autor_editar" class="form-label">Autor</label>
                        <select class="form-control" id="autor_editar" name="autor_id" required>
                            @foreach($autores as $autor)
                                <option value="{{ $autor->id }}">{{ $autor->nombre }} {{ $autor->apellido }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="dibujante_editar" class="form-label">Dibujante</label>
                        <select class="form-control" id="dibujante_editar" name="dibujante_id" required>
                            @foreach($dibujantes as $dibujante)
                                <option value="{{ $dibujante->id }}">{{ $dibujante->nombre }} {{ $dibujante->apellido }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Géneros</label>
                        <div class="border p-2 rounded" style="max-height: 200px; overflow-y: auto;">
                            <div class="row">
                                @foreach($generos as $index => $genero)
                                    <div class="col-3">
                                        <div class="form-check">
                                            <input class="form-check-input genero-checkbox" type="checkbox" id="genero_editar{{ $genero->id }}" name="generos[]" value="{{ $genero->id }}">
                                            <label class="form-check-label" for="genero_editar{{ $genero->id }}">{{ $genero->nombre }}</label>
                                        </div>
                                    </div>
                                    @if(($index + 1) % 4 == 0)
                                        </div><div class="row">
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="en_publicacion_editar" class="form-label">En Publicación</label>
                        <input type="checkbox" id="en_publicacion_editar" name="en_publicacion">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
 <!-- Modal para crear manga -->
 <div class="modal fade" id="modalCrear" tabindex="-1" aria-labelledby="modalCrearLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCrearLabel">Crear Manga</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formCrear" action="{{ route('mangas.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="titulo_crear" class="form-label">Título</label>
                        <input type="text" class="form-control" id="titulo_crear" name="titulo" required>
                    </div>
                    <div class="mb-3">
                        <label for="autor_crear" class="form-label">Autor</label>
                        <select class="form-control" id="autor_crear" name="autor_id" required>
                            @foreach($autores as $autor)
                                <option value="{{ $autor->id }}">{{ $autor->nombre }} {{ $autor->apellido }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="dibujante_crear" class="form-label">Dibujante</label>
                        <select class="form-control" id="dibujante_crear" name="dibujante_id" required>
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
                                            <input class="form-check-input genero-checkbox" type="checkbox" id="genero_crear{{ $genero->id }}" name="generos[]" value="{{ $genero->id }}">
                                            <label class="form-check-label" for="genero_crear{{ $genero->id }}">{{ $genero->nombre }}</label>
                                        </div>
                                    </div>
                                    @if(($index + 1) % 4 == 0)
                                        </div><div class="row">
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <!-- No se incluye el campo en_publicacion, ya que por defecto es true en la creación -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Manga</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
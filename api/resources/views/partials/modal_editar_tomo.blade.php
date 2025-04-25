<!-- Modal de Edición -->
<div class="modal fade" id="modalEdit-{{ $tomo->id }}" tabindex="-1" aria-labelledby="modalEditLabel-{{ $tomo->id }}" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditLabel-{{ $tomo->id }}">Editar Tomo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('tomos.update', $tomo->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <!-- Campo oculto para preservar la URL actual -->
                    <input type="hidden" name="redirect_to" value="{{ url()->full() }}">

                    <div class="mb-3">
                        <label for="manga_id_{{ $tomo->id }}" class="form-label">Manga</label>
                        <!-- Mostramos el manga en un select deshabilitado y enviamos su valor mediante un hidden -->
                        <select class="form-select" id="manga_id_{{ $tomo->id }}" disabled>
                            @foreach($mangas as $manga)
                                <option value="{{ $manga->id }}" {{ $manga->id == $tomo->manga_id ? 'selected' : '' }}>{{ $manga->titulo }}</option>
                            @endforeach
                        </select>
                        <input type="hidden" name="manga_id" value="{{ $tomo->manga_id }}">
                    </div>

                    <div class="mb-3">
                        <label for="editorial_id_{{ $tomo->id }}" class="form-label">Editorial</label>
                        <select class="form-select" name="editorial_id" id="editorial_id_{{ $tomo->id }}" required>
                            <option value="">Seleccione una editorial</option>
                            @foreach($editoriales as $editorial)
                                <option value="{{ $editorial->id }}" {{ $editorial->id == $tomo->editorial_id ? 'selected' : '' }}>{{ $editorial->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="formato_{{ $tomo->id }}" class="form-label">Formato</label>
                        <select class="form-select" name="formato" id="formato_{{ $tomo->id }}" required>
                            <option value="">Seleccione un formato</option>
                            <option value="Tankōbon" {{ $tomo->formato == 'Tankōbon' ? 'selected' : '' }}>Tankōbon</option>
                            <option value="Aizōban" {{ $tomo->formato == 'Aizōban' ? 'selected' : '' }}>Aizōban</option>
                            <option value="Kanzenban" {{ $tomo->formato == 'Kanzenban' ? 'selected' : '' }}>Kanzenban</option>
                            <option value="Bunkoban" {{ $tomo->formato == 'Bunkoban' ? 'selected' : '' }}>Bunkoban</option>
                            <option value="Wideban" {{ $tomo->formato == 'Wideban' ? 'selected' : '' }}>Wideban</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="idioma_{{ $tomo->id }}" class="form-label">Idioma</label>
                        <select class="form-select" name="idioma" id="idioma_{{ $tomo->id }}" required>
                            <option value="">Seleccione un idioma</option>
                            <option value="Español" {{ $tomo->idioma == 'Español' ? 'selected' : '' }}>Español</option>
                            <option value="Inglés" {{ $tomo->idioma == 'Inglés' ? 'selected' : '' }}>Inglés</option>
                            <option value="Japonés" {{ $tomo->idioma == 'Japonés' ? 'selected' : '' }}>Japonés</option>
                        </select>
                    </div>

                    <!-- Número de Tomo (no editable) -->
                    <div class="mb-3">
                        <label for="numero_tomo_{{ $tomo->id }}" class="form-label">Número de Tomo</label>
                        <input type="number" class="form-control" id="numero_tomo_{{ $tomo->id }}" name="numero_tomo" value="{{ $tomo->numero_tomo }}" readonly>
                    </div>

                    <!-- Fecha de Publicación -->
                    <div class="mb-3">
                        <label for="fecha_publicacion_{{ $tomo->id }}" class="form-label">Fecha de Publicación</label>
                        <input type="date" class="form-control" name="fecha_publicacion" id="fecha_publicacion_{{ $tomo->id }}" value="{{ $tomo->fecha_publicacion }}" required max="{{ date('Y-m-d') }}">
                    </div>

                    <div class="mb-3">
                        <label for="precio_{{ $tomo->id }}" class="form-label">Precio</label>
                        <input type="number" step="0.01" class="form-control" name="precio" id="precio_{{ $tomo->id }}" value="{{ $tomo->precio }}" required>
                    </div>

                    <!-- Campo Stock de solo lectura -->
                    <div class="mb-3">
                        <label for="stock_{{ $tomo->id }}" class="form-label">Stock</label>
                        <input type="number" class="form-control" id="stock_{{ $tomo->id }}" name="stock" value="{{ $tomo->stock }}" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="portada_{{ $tomo->id }}" class="form-label">Portada (Dejar en blanco para mantener la actual)</label>
                        <input type="file" class="form-control" name="portada" id="portada_{{ $tomo->id }}">
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

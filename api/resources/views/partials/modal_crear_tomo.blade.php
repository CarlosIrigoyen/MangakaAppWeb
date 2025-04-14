<!-- resources/views/partials/modal_crear_tomo.blade.php -->
<!-- Modal Crear Tomo -->
<div class="modal fade" id="modalCrearTomo" tabindex="-1" aria-labelledby="modalCrearTomoLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalCrearTomoLabel">Crear Tomo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <form action="{{ route('tomos.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="redirect_to" value="{{ url()->full() }}">
            <div class="mb-3">
              <label for="manga_id" class="form-label">Manga</label>
              <select class="form-select" name="manga_id" id="manga_id" required>
                <option value="">Seleccione un manga</option>
                @foreach($mangas as $manga)
                  <option value="{{ $manga->id }}">{{ $manga->titulo }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label for="editorial_id" class="form-label">Editorial</label>
              <select class="form-select" name="editorial_id" id="editorial_id" required>
                <option value="">Seleccione una editorial</option>
                @foreach($editoriales as $editorial)
                  <option value="{{ $editorial->id }}">{{ $editorial->nombre }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label for="formato" class="form-label">Formato</label>
              <select class="form-select" name="formato" id="formato" required>
                <option value="">Seleccione un formato</option>
                <option value="Tankōbon">Tankōbon</option>
                <option value="Aizōban">Aizōban</option>
                <option value="Kanzenban">Kanzenban</option>
                <option value="Bunkoban">Bunkoban</option>
                <option value="Wideban">Wideban</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="idioma" class="form-label">Idioma</label>
              <select class="form-select" name="idioma" id="idioma" required>
                <option value="">Seleccione un idioma</option>
                <option value="Español">Español</option>
                <option value="Inglés">Inglés</option>
                <option value="Japonés">Japonés</option>
              </select>
            </div>
            <!-- Campo Número de Tomo (no editable) -->
            <div class="mb-3">
              <label for="numero_tomo" class="form-label">Número de Tomo</label>
              <input type="number" class="form-control" id="numero_tomo" name="numero_tomo" readonly>
            </div>
            <!-- Campo Fecha de Publicación -->
            <div class="mb-3">
              <label for="fecha_publicacion" class="form-label">Fecha de Publicación</label>
              <input type="date" class="form-control" name="fecha_publicacion" id="fecha_publicacion" required>
            </div>
            <div class="mb-3">
              <label for="precio" class="form-label">Precio</label>
              <input type="number" step="0.01" class="form-control" name="precio" id="precio" required>
            </div>
            <!-- Campo Stock -->
            <div class="mb-3">
              <label for="stock" class="form-label">Stock</label>
              <input type="number" class="form-control" name="stock" id="stock" value="0" min="0" required>
            </div>
            <div class="mb-3">
              <label for="portada" class="form-label">Portada</label>
              <input type="file" class="form-control" name="portada" id="portada" required>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary">Crear Tomo</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

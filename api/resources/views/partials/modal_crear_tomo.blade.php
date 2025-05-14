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
              <select name="manga_id" id="manga_id" class="form-select" required>
                <option value="">Seleccione un manga</option>
                @foreach($mangas as $m)
                  <option value="{{ $m->id }}">{{ $m->titulo }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label for="editorial_id" class="form-label">Editorial</label>
              <select name="editorial_id" id="editorial_id" class="form-select" required>
                <option value="">Seleccione una editorial</option>
                @foreach($editoriales as $e)
                  <option value="{{ $e->id }}">{{ $e->nombre }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label for="formato" class="form-label">Formato</label>
              <select name="formato" id="formato" class="form-select" required>
                <option value="">Seleccione un formato</option>
                @foreach(['Tankōbon','Aizōban','Kanzenban','Bunkoban','Wideban'] as $fmt)
                  <option value="{{ $fmt }}">{{ $fmt }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label for="idioma" class="form-label">Idioma</label>
              <select name="idioma" id="idioma" class="form-select" required>
                <option value="">Seleccione un idioma</option>
                @foreach(['Español','Inglés','Japonés'] as $lang)
                  <option value="{{ $lang }}">{{ $lang }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label for="numero_tomo" class="form-label">Número de Tomo</label>
              <input type="number" id="numero_tomo" name="numero_tomo" class="form-control" readonly>
            </div>

            <div class="mb-3">
              <label for="fecha_publicacion" class="form-label">Fecha de Publicación</label>
              <input type="date" id="fecha_publicacion" name="fecha_publicacion" class="form-control" required>
            </div>

            <div class="mb-3">
              <label for="precio" class="form-label">Precio</label>
              <input type="number" step="0.01" min="0.01" id="precio"name="precio"class="form-control"required>
            </div>

            <div class="mb-3">
              <label for="stock" class="form-label">Stock</label>
              <input type="number" id="stock" name="stock" class="form-control" min="0" value="0" required>
            </div>

            <div class="mb-3">
              <label for="portada" class="form-label">Portada</label>
              <input type="file" id="portada" name="portada" class="form-control" required>
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

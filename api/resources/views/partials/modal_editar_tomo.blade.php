@php
    use Carbon\Carbon;

    // Obtener “tomo anterior” y “tomo siguiente” para este $tomo
    $tomoAnt = \App\Models\Tomo::withoutGlobalScope('activo')
                  ->where('manga_id', $tomo->manga_id)
                  ->where('editorial_id', $tomo->editorial_id)
                  ->where('numero_tomo', $tomo->numero_tomo - 1)
                  ->first();
    $fechaPrevio = $tomoAnt && $tomoAnt->fecha_publicacion
        ? Carbon::parse($tomoAnt->fecha_publicacion)->format('Y-m-d')
        : null;

    $tomoSig = \App\Models\Tomo::withoutGlobalScope('activo')
                  ->where('manga_id', $tomo->manga_id)
                  ->where('editorial_id', $tomo->editorial_id)
                  ->where('numero_tomo', $tomo->numero_tomo + 1)
                  ->first();
    $fechaSiguiente = $tomoSig && $tomoSig->fecha_publicacion
        ? Carbon::parse($tomoSig->fecha_publicacion)->format('Y-m-d')
        : null;
@endphp

<div class="modal fade" id="modalEdit-{{ $tomo->id }}" tabindex="-1" aria-labelledby="modalEditLabel-{{ $tomo->id }}" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('tomos.update', $tomo->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <!-- Preserva la URL actual para redirección -->
        <input type="hidden" name="redirect_to" value="{{ url()->full() }}">

        <div class="modal-header">
          <h5 class="modal-title" id="modalEditLabel-{{ $tomo->id }}">Editar Tomo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <div class="modal-body">
          <!-- Manga (read-only) -->
          <div class="mb-3">
            <label class="form-label">Manga</label>
            <select class="form-select" disabled>
              <option>{{ $tomo->manga->titulo }}</option>
            </select>
            <input type="hidden" name="manga_id" value="{{ $tomo->manga_id }}">
          </div>

          <!-- Editorial -->
          <div class="mb-3">
            <label for="editorial_id_{{ $tomo->id }}" class="form-label">Editorial</label>
            <select name="editorial_id" id="editorial_id_{{ $tomo->id }}" class="form-select" required>
              <option value="">Seleccione una editorial</option>
              @foreach($editoriales as $e)
                <option value="{{ $e->id }}" {{ $e->id == $tomo->editorial_id ? 'selected' : '' }}>
                  {{ $e->nombre }}
                </option>
              @endforeach
            </select>
          </div>

          <!-- Formato -->
          <div class="mb-3">
            <label for="formato_{{ $tomo->id }}" class="form-label">Formato</label>
            <select name="formato" id="formato_{{ $tomo->id }}" class="form-select" required>
              <option value="">Seleccione un formato</option>
              @foreach(['Tankōbon','Aizōban','Kanzenban','Bunkoban','Wideban'] as $fmt)
                <option value="{{ $fmt }}" {{ $tomo->formato == $fmt ? 'selected' : '' }}>
                  {{ $fmt }}
                </option>
              @endforeach
            </select>
          </div>

          <!-- Idioma -->
          <div class="mb-3">
            <label for="idioma_{{ $tomo->id }}" class="form-label">Idioma</label>
            <select name="idioma" id="idioma_{{ $tomo->id }}" class="form-select" required>
              <option value="">Seleccione un idioma</option>
              @foreach(['Español','Inglés','Japonés'] as $lang)
                <option value="{{ $lang }}" {{ $tomo->idioma == $lang ? 'selected' : '' }}>
                  {{ $lang }}
                </option>
              @endforeach
            </select>
          </div>

          <!-- Número de Tomo (no editable) -->
          <div class="mb-3">
            <label class="form-label">Número de Tomo</label>
            <input type="number" class="form-control" value="{{ $tomo->numero_tomo }}" readonly>
          </div>

          <!-- Fecha de Publicación -->
          <div class="mb-3">
            <label for="fecha_publicacion_{{ $tomo->id }}" class="form-label">Fecha de Publicación</label>
            <input
              type="date"
              name="fecha_publicacion"
              id="fecha_publicacion_{{ $tomo->id }}"
              class="form-control"
              value="{{ $tomo->fecha_publicacion ? Carbon::parse($tomo->fecha_publicacion)->format('Y-m-d') : '' }}"
              @if($fechaPrevio) min="{{ $fechaPrevio }}" @endif
              @if($fechaSiguiente) max="{{ $fechaSiguiente }}" @endif
              required
            >
            @if($fechaPrevio || $fechaSiguiente)
              <small class="form-text text-muted">
                @if($fechaPrevio && $fechaSiguiente)
                  Entre {{ $fechaPrevio }} y {{ $fechaSiguiente }}
                @elseif($fechaPrevio)
                  Desde {{ $fechaPrevio }}
                @elseif($fechaSiguiente)
                  Hasta {{ $fechaSiguiente }}
                @endif
              </small>
            @endif
          </div>

          <!-- Precio -->
          <div class="mb-3">
            <label for="precio_{{ $tomo->id }}" class="form-label">Precio</label>
            <input
              type="number"
              step="0.01"
              min="0.01"
              name="precio"
              id="precio_{{ $tomo->id }}"
              class="form-control"
              value="{{ $tomo->precio }}"
              required
            >
          </div>

          <!-- Stock (read-only) -->
          <div class="mb-3">
            <label class="form-label">Stock</label>
            <input type="number" class="form-control" value="{{ $tomo->stock }}" readonly>
          </div>

          <!-- Portada (opcional) -->
          <div class="mb-3">
            <label for="portada_{{ $tomo->id }}" class="form-label">Portada (dejar en blanco para mantener actual)</label>
            <input type="file" name="portada" id="portada_{{ $tomo->id }}" class="form-control">
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar Cambios</button>
        </div>

      </form>
    </div>
  </div>
</div>

<script>
  document.querySelectorAll('input[type="number"][name="precio"]').forEach(function(input) {
    input.addEventListener('input', function() {
      const val = parseFloat(this.value);
      if (isNaN(val) || val <= 0) {
        this.setCustomValidity('El precio debe ser mayor que 0');
      } else {
        this.setCustomValidity('');
      }
    });
  });
</script>

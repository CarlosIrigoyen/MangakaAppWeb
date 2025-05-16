<!-- Modal de Información -->
<div class="modal fade" id="modalInfo-{{ $tomo->id }}" tabindex="-1" aria-labelledby="modalInfoLabel-{{ $tomo->id }}" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalInfoLabel-{{ $tomo->id }}">Detalles del Tomo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <h5>{{ $tomo->manga->titulo }} - Tomo {{ $tomo->numero_tomo }}</h5>

          <p><strong>Editorial:</strong> {{ $tomo->editorial->nombre ?? 'N/A' }}</p>
          <p><strong>Formato:</strong> {{ $tomo->formato }}</p>
          <p><strong>Idioma:</strong> {{ $tomo->idioma }}</p>
          <p><strong>Precio:</strong> ${{ $tomo->precio }}</p>
          <p><strong>Stock:</strong> {{ $tomo->stock }}</p>

          <p><strong>Estado:</strong>
            @if($tomo->activo)
              <span class="badge bg-success">Activo</span>
            @else
              <span class="badge bg-secondary">Inactivo</span>
            @endif
          </p>

          <p><strong>Autor:</strong> {{ optional($tomo->manga->autor)->nombre ?? 'N/A' }} {{ optional($tomo->manga->autor)->apellido ?? '' }}</p>
          <p><strong>Dibujante:</strong> {{ optional($tomo->manga->dibujante)->nombre ?? 'N/A' }} {{ optional($tomo->manga->dibujante)->apellido ?? '' }}</p>

          <p><strong>Géneros:</strong>
            @if($tomo->manga->generos->isNotEmpty())
              {{ $tomo->manga->generos->pluck('nombre')->join(', ') }}
            @else
              N/A
            @endif
          </p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  </div>

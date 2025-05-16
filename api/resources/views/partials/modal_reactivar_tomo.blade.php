{{-- Partial: Modal Reactivar Tomo --}}
<div class="modal fade" id="modalReactivate-{{ $tomo->id }}" tabindex="-1" aria-labelledby="modalReactivateLabel-{{ $tomo->id }}" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalReactivateLabel-{{ $tomo->id }}">Confirmar Reactivación</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          ¿Estás seguro de que deseas dar de alta este tomo?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <form action="{{ route('tomos.reactivate', $tomo->id) }}" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="redirect_to" value="{{ url()->full() }}">
            <button type="submit" class="btn btn-success">
              <i class="fas fa-check-circle"></i> Sí, dar de alta
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

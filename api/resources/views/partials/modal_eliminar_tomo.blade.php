<!-- Modal de Eliminación -->
<div class="modal fade" id="modalDelete-{{ $tomo->id }}" tabindex="-1" aria-labelledby="modalDeleteLabel-{{ $tomo->id }}" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalDeleteLabel-{{ $tomo->id }}">Dar De Baja Tomo</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          ¿Estás seguro de que deseas dar de baja  este tomo?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <form action="{{ route('tomos.destroy', $tomo->id) }}" method="POST" class="d-inline">
            @csrf
            @method('DELETE')
            <!-- Conservamos filtros/página -->
            <input type="hidden" name="redirect_to" value="{{ url()->full() }}">
            <button type="submit" class="btn btn-danger">Dar De Baja</button>
          </form>
        </div>
      </div>
    </div>
  </div>

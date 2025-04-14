    <!-- Modal de Eliminación -->
    <div class="modal fade" id="modalDelete-{{ $tomo->id }}" tabindex="-1" aria-labelledby="modalDeleteLabel-{{ $tomo->id }}" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDeleteLabel-{{ $tomo->id }}">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    ¿Estás seguro de que deseas eliminar este tomo?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="{{ route('tomos.destroy', $tomo->id) }}" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="redirect_to" value="{{ url()->full() }}">
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
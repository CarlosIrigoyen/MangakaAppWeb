<!-- Modal para eliminar manga -->
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-labelledby="modalEliminarLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalEliminarLabel">Eliminar Manga</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <p id="eliminar-body-text">¿Estás seguro de que deseas eliminar este manga?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <form id="formEliminar" action="" method="POST" style="display:inline;">
            @csrf
            @method('DELETE')
            <button id="btnConfirmEliminar" type="submit" class="btn btn-danger">
              Eliminar
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

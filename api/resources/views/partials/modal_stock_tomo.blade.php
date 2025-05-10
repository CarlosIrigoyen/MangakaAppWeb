<!-- Modal de Stock Bajo -->
<div class="modal fade" id="modalStock" tabindex="-1" aria-labelledby="modalStockLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form action="{{ route('tomos.updateMultipleStock') }}" method="POST">
          @csrf
          @method('PUT')
          <div class="modal-header">
            <h5 class="modal-title" id="modalStockLabel">Tomos con bajo stock (menos de 10 unidades)</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <table class="table table-bordered">
              <thead>
                <tr>
                  <th>Manga</th>
                  <th>Número de Tomo</th>
                  <th>Stock Actual</th>
                  <th>Nuevo Stock</th>
                </tr>
              </thead>
              <tbody>
                @foreach($lowStockTomos as $tomo)
                  <tr>
                    <td>{{ $tomo->manga->titulo }}</td>
                    <td>{{ $tomo->numero_tomo }}</td>
                    <td>{{ $tomo->stock }}</td>
                    <td>
                      <input type="hidden" name="tomos[{{ $tomo->id }}][id]" value="{{ $tomo->id }}">
                      <input type="number"
                             name="tomos[{{ $tomo->id }}][stock]"
                             class="form-control"
                             value="{{ $tomo->stock }}"
                             min="{{ $tomo->stock }}">
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">Actualizar Stock</button>
          </div>
        </form>
      </div>
    </div>
  </div>

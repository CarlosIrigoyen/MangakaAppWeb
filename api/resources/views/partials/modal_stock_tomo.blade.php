<!-- Botón para ver stock bajo (se muestra si hay tomos con stock < 10) -->
<div class="modal fade" id="modalStock" tabindex="-1" aria-labelledby="modalStockLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tomos con bajo stock (menos de 10 unidades)</h5>
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
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lowStockTomos as $tomo)
                        <tr>
                            <td>{{ $tomo->manga->titulo }}</td>
                            <td>{{ $tomo->numero_tomo }}</td>
                            <td>{{ $tomo->stock }}</td>
                            <td>
                                <!-- Formulario para actualizar el stock de este tomo -->
                                <form action="{{ route('tomos.updateStock', $tomo->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <input type="number" name="stock" class="form-control" value="{{ $tomo->stock }}" min="{{ $tomo->stock }}">
                            </td>
                            <td>
                                    <button type="submit" class="btn btn-primary btn-sm">Actualizar</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

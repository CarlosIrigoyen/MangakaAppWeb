<div class="pagination-container">
    <button class="btn btn-light"
            onclick="window.location.href='{{ $tomos->previousPageUrl() }}'"
            {{ $tomos->onFirstPage() ? 'disabled' : '' }}>
        &laquo; Anterior
    </button>
    <span id="pageIndicator">
        Página {{ $tomos->currentPage() }} / {{ $tomos->lastPage() }}
    </span>
    <button class="btn btn-light"
            onclick="window.location.href='{{ $tomos->nextPageUrl() }}'"
            {{ $tomos->currentPage() == $tomos->lastPage() ? 'disabled' : '' }}>
        Siguiente &raquo;
    </button>
</div>
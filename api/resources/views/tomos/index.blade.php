@extends('adminlte::page')

@section('title', 'Listado de Tomos')

@section('content_header')
    <h1>Listado De Tomos</h1>
@stop

@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.css">
<link rel="stylesheet" href="{{ asset('css/tomos.css') }}">
@stop

@section('content')

<div class="container">

@include('partials.filtros_tomo')

@if($tomos->total() == 0)

<div class="alert alert-warning text-center">
No se encontraron tomos.
</div>

@else

<div class="card mt-3">
<div class="card-body">

<div class="row" id="tomoList">

@foreach($tomos as $tomo)

<div class="col-md-4 mb-4">

<div class="card card-tomo">

<img src="{{ asset($tomo->portada) }}" class="card-img-top" alt="Portada">

<div class="card-footer d-flex flex-column gap-2">

@if(request('status') === 'inactivo' && !$tomo->activo)

<button
type="button"
class="btn btn-sm btn-success"
data-bs-toggle="modal"
data-bs-target="#modalReactivate-{{ $tomo->id }}"
>
<i class="fas fa-check-circle"></i>
Dar de alta
</button>

@else

<button type="button" class="btn btn-sm btn-info"
data-bs-toggle="modal"
data-bs-target="#modalInfo-{{ $tomo->id }}">
<i class="fas fa-info-circle"></i>
Información
</button>

<button type="button" class="btn btn-sm btn-warning"
data-bs-toggle="modal"
data-bs-target="#modalEdit-{{ $tomo->id }}">
<i class="fas fa-edit"></i>
Editar
</button>

<button type="button" class="btn btn-sm btn-danger"
data-bs-toggle="modal"
data-bs-target="#modalDelete-{{ $tomo->id }}">
<i class="fas fa-trash"></i>
Dar De Baja
</button>

@endif

</div>
</div>

@include('partials.modal_info_tomo', ['tomo' => $tomo])

@include('partials.modal_editar_tomo', [
'tomo' => $tomo,
'mangas' => $mangas,
'editoriales' => $editoriales
])

<div class="modal fade"
id="modalDelete-{{ $tomo->id }}"
tabindex="-1"
aria-labelledby="modalDeleteLabel-{{ $tomo->id }}"
aria-hidden="true">

<div class="modal-dialog">

<div class="modal-content">

<div class="modal-header">

<h5 class="modal-title"
id="modalDeleteLabel-{{ $tomo->id }}">
Dar De Baja Tomo
</h5>

<button type="button"
class="btn-close"
data-bs-dismiss="modal">
</button>

</div>

<div class="modal-body">

¿Estás seguro de que deseas dar de baja este tomo?

</div>

<div class="modal-footer">

<button type="button"
class="btn btn-secondary"
data-bs-dismiss="modal">
Cancelar
</button>

<form action="{{ route('tomos.destroy', $tomo->id) }}"
method="POST">

@csrf
@method('DELETE')

<input type="hidden"
name="redirect_to"
value="{{ url()->full() }}">

<button type="submit"
class="btn btn-danger">
Dar De Baja
</button>

</form>

</div>
</div>
</div>
</div>

@include('partials.modal_reactivar_tomo', ['tomo' => $tomo])

</div>

@endforeach

</div>
</div>
</div>

<div class="separator"></div>

<div class="pagination-container">

<button
class="btn btn-light"
onclick="window.location.href='{{ $tomos->previousPageUrl() }}'"
{{ $tomos->onFirstPage() ? 'disabled' : '' }}
>
&laquo; Anterior
</button>

<span>
Página {{ $tomos->currentPage() }}
/
{{ $tomos->lastPage() }}
</span>

<button
class="btn btn-light"
onclick="window.location.href='{{ $tomos->nextPageUrl() }}'"
{{ $tomos->currentPage() == $tomos->lastPage() ? 'disabled' : '' }}
>
Siguiente &raquo;
</button>

</div>

@endif

</div>

@include('partials.modal_crear_tomo')

@include('partials.modal_stock_tomo')

@stop

@section('js')

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.min.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.min.js"></script>

<script>

$(document).ready(function(){

$('.filter-radio').on('change', function(){

var type = $(this).val();

$('.filter-select')
.hide()
.prop('disabled', true);

$('#filterSelectContainer').show();

$('#select-'+type)
.show()
.prop('disabled', false);

});

var curr = "{{ request()->get('filter_type') }}";

if(curr){

$('.filter-radio[value="'+curr+'"]').prop('checked', true);

$('#filterSelectContainer').show();

$('#select-'+curr)
.show()
.prop('disabled', false);

}

const nextTomos = @json($nextTomos);

function actualizarCampos(){

var m = $('#manga_id').val();
var e = $('#editorial_id').val();

var info = (nextTomos[m]||{})[e] || {};

$('#numero_tomo').val(info.numero ?? '');
$('#precio').val(info.precio ?? '');

if (info.formato) $('#formato').val(info.formato);
if (info.idioma) $('#idioma').val(info.idioma);

if (info.fechaMin) {

$('#fecha_publicacion')
.attr('min', info.fechaMin)
.val(info.fechaMin);

} else {

$('#fecha_publicacion')
.removeAttr('min')
.val('');

}

}

$('#manga_id, #editorial_id')
.on('change', actualizarCampos);

$('#modalCrearTomo').on('show.bs.modal', function(){

$('#modalCrearTomo form')[0].reset();
$('#fecha_publicacion').removeAttr('min');

});

});

</script>

@stop

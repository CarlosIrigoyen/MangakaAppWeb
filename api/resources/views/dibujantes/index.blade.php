@extends('adminlte::page')

@section('title', 'Listado de Dibujantes')

@section('content_header')
    <h1>Listado de Dibujantes</h1>
@stop

@section('content')
    <!-- Contenedor con el Card de Bootstrap -->
    <div class="container">
        @include('partials.listado_dibujante')
    </div>
    @include('partials.modal_eliminar_dibujante')
    @include('partials.modal_editar_dibujante')
    @include('partials.modal_crear_dibujante')
@stop

@section('css')
    <!-- Cargar CSS de DataTables anticipadamente para evitar parpadeos -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"> <!-- FontAwesome para los iconos -->
    <style>
        #Contenido {
            visibility: hidden;
        }
    </style>
@stop

@section('js')
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.js"></script>
    <script src="{{ asset('js/dibujantes.js') }}"></script>
@stop
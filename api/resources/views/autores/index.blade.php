@extends('adminlte::page')

@section('title', 'Listado de Autores')

@section('content_header')
    <h1>Listado de Autores</h1>
@stop

@section('content')
    @include('partials.listado_autor')
    @include('partials.modal_eliminar_autor')
    @include('partials.modal_editar_autor')
    @include('partials.modal_crear_autor')


@stop

@section('css')
    <!-- Estilos para DataTables y FontAwesome -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        #Contenido { visibility: hidden; }
    </style>
@stop

@section('js')
    <!-- Scripts de jQuery, Bootstrap y DataTables -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.js"></script>
    <script src="{{ asset('js/autor.js') }}"></script>
@stop

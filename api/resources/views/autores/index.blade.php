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
    <!-- Estilos para DataTables, Bootstrap y FontAwesome -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Asegúrate de que el contenedor esté visible al momento de inicializar DataTables -->
@stop

@section('js')
    <!-- Scripts de jQuery, Bootstrap y DataTables -->
    <script src="https://code.jquery.com/jquery-3.7.1.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/dataTables.responsive.js"></script>
    <script src="https://cdn.datatables.net/responsive/3.0.3/js/responsive.bootstrap5.js"></script>
    <script>
        $(document).ready(function() {
            // Inicialización de DataTable con las opciones recomendadas
            var table = $('#autoresTable').DataTable({
                responsive: true,
                autoWidth: false,
                // Puedes incluir opciones adicionales según tus necesidades
            });

            // Evento para ajustar la tabla al cambiar la orientación o redimensionar la ventana
            $(window).on('orientationchange resize', function(){
                table.columns.adjust().responsive.recalc();
            });

            // En caso de que un modal modifique el layout, ajustar la tabla al mostrarse
            $('#modalEditar, #modalCrear, #modalEliminar').on('shown.bs.modal', function () {
                table.columns.adjust().responsive.recalc();
            });
        });
    </script>
    <script src="{{ asset('js/autor.js') }}"></script>
@stop
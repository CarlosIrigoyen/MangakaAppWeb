@extends('adminlte::page')

@section('title', 'Listado de Editoriales')

@section('content_header')
    <h1>Listado de Generos</h1>
@stop

@section('content')
    @include('partials.listado_genero')
    @include('partials.modal_eliminar_genero')
    @include('partials.modal_editar_genero')
    @include('partials.modal_crear_genero')
@stop

@section('css')
    <!-- Cargar CSS de DataTables, Bootstrap y FontAwesome -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Ocultar la tabla inicialmente para evitar parpadeos -->
    <style>
        /* Se usa el id "editorialesTable" para la tabla */
        #generosTable {
            visibility: hidden;
        }
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

    <script>
        $(document).ready(function() {
            // Inicialización de DataTable para editoriales usando el id "editorialesTable"
            var table = $('#generosTable').DataTable({
                responsive: true,
                autoWidth: false,"language": {
                    "lengthMenu": "Mostrar _MENU_ registros por página",
                    "zeroRecords": "No se encontraron resultados",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                    "infoFiltered": "(filtrado de _MAX_ registros totales)",
                    "search": "Buscar:",
                    emptyTable: "No se encontraron generos",

                },
                initComplete: function () {
                    // Mostrar la tabla una vez finalizada la inicialización
                    $('#generosTable').css('visibility', 'visible');
                }
            });

            // Recalcular columnas al cambiar la orientación o redimensionar la ventana
            $(window).on('orientationchange resize', function(){
                table.columns.adjust().responsive.recalc();
            });

            // Forzar el ajuste de columnas cuando se muestren los modales (crear, editar o eliminar)
            $('#modalEditar, #modalCrear, #modalEliminar').on('shown.bs.modal', function () {
                table.columns.adjust().responsive.recalc();
            });
        });
    </script>
    <script src="{{ asset('js/genero.js') }}"></script>
@stop

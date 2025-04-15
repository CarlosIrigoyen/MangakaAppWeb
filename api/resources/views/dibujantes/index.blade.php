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
    <!-- Cargar CSS de DataTables, Bootstrap y FontAwesome -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.3/css/responsive.bootstrap5.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Ocultar la tabla inicialmente para evitar parpadeos -->
    <style>
        /* Se usa el id "dibujantesTable" en la tabla */
        #dibujantesTable {
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
            // Inicialización de DataTable para dibujantes usando el id "dibujantesTable"
            var table = $('#dibujantesTable').DataTable({
                responsive: true,
                autoWidth: false,
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
                },
                initComplete: function () {
                    // Una vez finalizada la inicialización, se muestra la tabla
                    $('#dibujantesTable').css('visibility', 'visible');
                }
            });

            // Ajustar columnas al redimensionar la ventana o al cambiar la orientación
            $(window).on('orientationchange resize', function(){
                table.columns.adjust().responsive.recalc();
            });

            // Forzar ajuste de columnas cuando se muestran los modales (si afectan al layout)
            $('#modalEditar, #modalCrear, #modalEliminar').on('shown.bs.modal', function () {
                table.columns.adjust().responsive.recalc();
            });
        });
    </script>

    <!-- <script src="{{ asset('js/dibujantes.js') }}"></script> -->
@stop

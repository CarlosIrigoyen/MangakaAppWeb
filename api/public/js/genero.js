$(document).ready(function() {
    // Inicialización de DataTable para géneros (usando id="Contenido")
    var table = $('#Contenido').DataTable({
        responsive: true,
        autoWidth: false,
        language: {
            lengthMenu: "Mostrar _MENU_ registros por página",
            zeroRecords: "No se encontraron resultados",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Mostrando 0 a 0 de 0 registros",
            infoFiltered: "(filtrado de _MAX_ registros totales)",
            search: "Buscar:",
            emptyTable: "No se encontraron géneros"
        },
        initComplete: function () {
            $('#Contenido').css('visibility', 'visible');
        }
    });

    // Reajustar columnas al cambiar orientación o al mostrar modales
    $(window).on('orientationchange resize', function(){
        table.columns.adjust().responsive.recalc();
    });
    $('#modalCrear, #modalEditar, #modalEliminar').on('shown.bs.modal', function () {
        table.columns.adjust().responsive.recalc();
    });

    // Función AJAX para editar género
    window.editarGenero = function(id) {
        $.ajax({
            url: '/generos/' + id + '/edit',
            method: 'GET',
            success: function(data) {
                $('#formEditar #nombre').val(data.nombre);
                $('#formEditar').attr('action', '/generos/' + id);
            }
        });
    };

    // Función para configurar la eliminación del género
    window.configurarEliminar = function(id) {
        // Texto y botón por defecto
        $('#eliminar-body-text')
            .text('¿Estás seguro de que deseas dar de baja este género?');
        $('#btnConfirmEliminar')
            .prop('disabled', false)
            .text('Eliminar');

        // Actualizar acción del form
        $('#formEliminar').attr('action', '/generos/' + id);

        // AJAX para contar mangas asociados
        $.ajax({
            url: '/generos/' + id + '/check-mangas',
            method: 'GET',
            success: function(data) {
                if (data.mangas_count > 0) {
                    $('#eliminar-body-text').html(
                        'El género <strong>' + data.nombre + '</strong> ya tiene mangas asociados y no se puede dar de baja.'
                    );
                    $('#btnConfirmEliminar')
                        .prop('disabled', true)
                        .text('No se puede dar de baja ');
                }
            },
            error: function() {
                $('#eliminar-body-text')
                    .text('Error al comprobar dependencias.');
                $('#btnConfirmEliminar').prop('disabled', true);
            }
        });
    };
});

$(document).ready(function() {
    // Inicializa DataTables
    $('#Contenido').DataTable({
        responsive: true,
        autoWidth: false,
        language: {
            lengthMenu: "Mostrar _MENU_ registros por página",
            zeroRecords: "No se encontraron resultados",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Mostrando 0 a 0 de 0 registros",
            infoFiltered: "(filtrado de _MAX_ registros totales)",
            search: "Buscar:",
            emptyTable: "No se encontraron editoriales"
        },
        initComplete: function() {
            $('#Contenido').css('visibility', 'visible');
        }
    });

    // Ajustar columnas si se muestran modales
    $('#modalCrear, #modalEditar, #modalEliminar').on('shown.bs.modal', function() {
        $('#Contenido').DataTable().columns.adjust().responsive.recalc();
    });
});

// Función para editar editorial
function editarEditorial(id) {
    $.ajax({
        url: '/editoriales/' + id + '/edit',
        method: 'GET',
        success: function(data) {
            $('#nombre').val(data.nombre);
            $('#pais').val(data.pais);
            $('#formEditar').attr('action', '/editoriales/' + id);
        }
    });
}

// Función para preparar y mostrar el modal de eliminación con chequeo de tomos asociados
function configurarEliminar(id) {
    // 1) Actualizar acción del form
    $('#formEliminar').attr('action', '/editoriales/' + id);

    // 2) Reset del modal
    $('#eliminar-body-text')
      .text('¿Estás seguro de que deseas eliminar esta editorial?');
    $('#btnConfirmEliminar')
      .prop('disabled', false)
      .text('Eliminar');

    // 3) Comprobar tomos vía AJAX
    $.ajax({
        url: '/editoriales/' + id + '/check-tomos',
        method: 'GET',
        success: function(data) {
            if (data.tomos_count > 0) {
                $('#eliminar-body-text').html(
                    'La editorial <strong>' + data.nombre + '</strong> tiene ' +
                    data.tomos_count + ' tomo(s) asociados y no se puede eliminar.'
                );
                $('#btnConfirmEliminar')
                  .prop('disabled', true)
                  .text('No se puede eliminar');
            }
        },
        error: function() {
            $('#eliminar-body-text').text('Error al comprobar dependencias.');
            $('#btnConfirmEliminar').prop('disabled', true);
        }
    });
}

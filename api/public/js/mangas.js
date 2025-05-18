$(document).ready(function() {
    $('#Contenido').DataTable({
        responsive: true,
        order: [[0, 'desc']],
        language: {
            lengthMenu: "Mostrar _MENU_ registros por página",
            zeroRecords: "No se encontraron resultados",
            info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
            infoEmpty: "Mostrando 0 a 0 de 0 registros",
            infoFiltered: "(filtrado de _MAX_ registros totales)",
            search: "Buscar:"
        },
        initComplete: function() {
            $('#Contenido').css('visibility', 'visible');
        }
    });
});

// Función para preparar el modal de eliminación y comprobar dependencias
function configurarEliminar(mangaId) {
    // 1) acción del form
    $('#formEliminar').attr('action', '/mangas/' + mangaId);

    // 2) reset del modal (texto y botón)
    $('#modalEliminar .modal-body').text('¿Estás seguro de que deseas dar de baja este manga?');
    $('#btnConfirmEliminar')
      .prop('disabled', false)
      .text('Eliminar');

    // 3) petición AJAX para contar tomos asociados
    $.ajax({
        url: '/mangas/' + mangaId + '/check-tomos',
        method: 'GET',
        success: function(data) {
            if (data.tomos_count > 0) {
                $('#modalEliminar .modal-body').html(
                    'El manga <strong>"' + data.titulo + '"</strong> tiene ' +
                    data.tomos_count + ' tomo(s) asociados y no se puede dar de baja.'
                );
                $('#btnConfirmEliminar')
                  .prop('disabled', true)
                  .text('No se puede dar de baja');
            }
            // si no tiene tomos, deja el modal listo para confirmar
        },
        error: function() {
            $('#modalEliminar .modal-body').text('Error al comprobar dependencias.');
            $('#btnConfirmEliminar')
              .prop('disabled', true)
              .text('No se puede eliminar');
        }
    });
}

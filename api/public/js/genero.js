// Función para editar genero
function editarGenero(id) {
    $.ajax({
        url: '/generos/' + id + '/edit',
        method: 'GET',
        success: function(data) {
            $('#nombre').val(data.nombre);
                $('#formEditar').attr('action', '/generos/' + id);
        }
    });
}

// Función para configurar la eliminación del genero
function configurarEliminar(id) {
    $('#formEliminar').attr('action', '/generos/' + id);
}

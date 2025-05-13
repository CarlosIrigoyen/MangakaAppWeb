$(document).ready(function() {
    // Inicializa DataTables
    $('#Contenido').DataTable({
        "responsive": true,
        "autoWidth": false,
        "language": {
            "lengthMenu": "Mostrar _MENU_ registros por página",
            "zeroRecords": "No se encontraron resultados",
            "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
            "infoEmpty": "Mostrando 0 a 0 de 0 registros",
            "infoFiltered": "(filtrado de _MAX_ registros totales)",
            "search": "Buscar:",
            emptyTable: "No se encontraron dibujantes",

        }
    });
    $('#Contenido').css('visibility', 'visible');
    // Evento para ajustar la tabla al cambiar la orientación o redimensionar la ventana
    $(window).on('orientationchange resize', function(){
        table.columns.adjust().responsive.recalc();
    });

    // En caso de que un modal modifique el layout, ajustar la tabla al mostrarse
    $('#modalEditar, #modalCrear, #modalEliminar').on('shown.bs.modal', function () {
        table.columns.adjust().responsive.recalc();
    });
    // Validación en tiempo real para el formulario de creación
    function validateCreateForm() {
        var nombre = $('#nombre').val();
        var apellido = $('#apellido').val();
        var fecha = $('#fecha_nacimiento').val();
        var isValid = true;
        // Expresión regular para letras y espacios (soporta Unicode)
        var nameRegex = /^[\p{L}\s]+$/u;

        // Validación del nombre
        if (!nameRegex.test(nombre)) {
            isValid = false;
            $('#nombreError').text('El nombre solo puede contener letras y espacios.');
        } else {
            $('#nombreError').text('');
        }

        // Validación del apellido
        if (!nameRegex.test(apellido)) {
            isValid = false;
            $('#apellidoError').text('El apellido solo puede contener letras y espacios.');
        } else {
            $('#apellidoError').text('');
        }

        // Validación de la fecha de nacimiento
        if (fecha === '') {
            isValid = false;
            $('#fechaError').text('La fecha de nacimiento es requerida.');
        } else {
            var birthDate = new Date(fecha);
            var today = new Date();
            var age = today.getFullYear() - birthDate.getFullYear();
            var m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            if (age < 18) {
                isValid = false;
                $('#fechaError').text('El dibujante debe tener al menos 18 años.');
            } else {
                $('#fechaError').text('');
            }
        }
        // Habilitar o deshabilitar el botón de crear
        $('#crearButton').prop('disabled', !isValid);
    }

    // Validación en tiempo real para el formulario de edición
    function validateEditForm() {
        var nombre = $('#nombre_edit').val();
        var apellido = $('#apellido_edit').val();
        var fecha = $('#fecha_nacimiento_edit').val();
        var isValid = true;
        var nameRegex = /^[\p{L}\s]+$/u;

        if (!nameRegex.test(nombre)) {
            isValid = false;
            $('#nombreErrorEdit').text('El nombre solo puede contener letras y espacios.');
        } else {
            $('#nombreErrorEdit').text('');
        }
        if (!nameRegex.test(apellido)) {
            isValid = false;
            $('#apellidoErrorEdit').text('El apellido solo puede contener letras y espacios.');
        } else {
            $('#apellidoErrorEdit').text('');
        }
        if (fecha === '') {
            isValid = false;
            $('#fechaErrorEdit').text('La fecha de nacimiento es requerida.');
        } else {
            var birthDate = new Date(fecha);
            var today = new Date();
            var age = today.getFullYear() - birthDate.getFullYear();
            var m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            if (age < 18) {
                isValid = false;
                $('#fechaErrorEdit').text('El dibujante debe tener al menos 18 años.');
            } else {
                $('#fechaErrorEdit').text('');
            }
        }
        $('#editButton').prop('disabled', !isValid);
    }

    // Eventos para validar en tiempo real el formulario de creación
    $('#nombre, #apellido, #fecha_nacimiento').on('input change', validateCreateForm);
    // Eventos para validar en tiempo real el formulario de edición
    $('#nombre_edit, #apellido_edit, #fecha_nacimiento_edit').on('input change', validateEditForm);
});

// Función para cargar datos en el formulario de edición
function editarDibujante(id) {
    $.ajax({
        url: '/dibujantes/' + id + '/edit',
        method: 'GET',
        success: function(data) {
            $('#nombre_edit').val(data.nombre);
            $('#apellido_edit').val(data.apellido);
            $('#fecha_nacimiento_edit').val(data.fecha_nacimiento);
            $('#formEditar').attr('action', '/dibujantes/' + id);
            // Reiniciar mensajes de error y deshabilitar el botón
            $('#nombreErrorEdit, #apellidoErrorEdit, #fechaErrorEdit').text('');
            $('#editButton').prop('disabled', true);
        }
    });
}

// Función para configurar la eliminación del dibujante
function configurarEliminar(id) {
    $('#eliminar-body-text').text('¿Estás seguro de que deseas eliminar este dibujante?');
    $('#btnConfirmEliminar').prop('disabled', false).text('Eliminar');
    $('#formEliminar').attr('action', '/dibujantes/' + id);

    $.ajax({
        url: '/dibujantes/' + id + '/check-mangas',
        method: 'GET',
        success: function(data) {
            if (data.mangas_count > 0) {
                $('#eliminar-body-text').html(
                    'El dibujante <strong>' + data.nombre + '</strong> tiene ' +
                    data.mangas_count + ' manga(s) asociados y no se puede eliminar.'
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


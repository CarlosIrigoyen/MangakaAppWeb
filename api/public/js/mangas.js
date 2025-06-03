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

// public/js/mangas.js

/**
 * Función que se dispara al hacer clic en "Editar" y recibe todo el objeto Manga
 * (con sus relaciones cargadas) gracias a {!! json_encode($m) !!}
 */
function editarManga(manga) {
    // 1) Ajustar el 'action' del formulario para que apunte a la ruta de actualización
    //    (en Laravel, normalmente: /mangas/{id} con método PUT)
    //    Si tu ruta se llama 'mangas.update', y el URI es '/mangas/{manga}',
    //    podrías componerla así:
    let urlUpdate = `/mangas/${manga.id}`;
    $('#formEditar').attr('action', urlUpdate);

    // 2) Poner el ID en el campo oculto (si decides usarlo; opcional si solo usas la URL)
    $('#manga_id').val(manga.id);

    // 3) Rellenar el campo de título
    $('#titulo_editar').val(manga.titulo);

    // 4) Seleccionar en los <select> de autor y dibujante
    //    Ten en cuenta que, al hacer json_encode($m), 'manga.autor' es un objeto
    //    con clave 'id' (y nombre/apellido). Lo mismo para 'manga.dibujante'.
    $('#autor_editar').val(manga.autor.id);
    $('#dibujante_editar').val(manga.dibujante.id);

    // 5) Desmarcar todos los checkboxes de géneros primero
    $('.genero-checkbox').prop('checked', false);

    //    Luego, marcar solo aquellos géneros que pertenezcan a este $manga.
    //    La propiedad 'manga.generos' es un array de objetos género (con id, nombre, etc).
    manga.generos.forEach(function(g) {
        // El id de cada checkbox en tu blade es "genero_editar{ID}"
        $(`#genero_editar${g.id}`).prop('checked', true);
    });

    // 6) Marcar/desmarcar el checkbox de "en_publicación"
    //    En tu base de datos, parece que guardas 'si' o 'no' en el campo en_publicacion.
    //    Entonces:
    $('#en_publicacion_editar').prop('checked', manga.en_publicacion === 'si');

    // 7) Finalmente, abrir el modal
    $('#modalEditar').modal('show');
}

// Importante: si tu proyecto usa ES6/modules, revisa la forma de exponer esta función.
// Si no usas módulos, bastará con que quede en ámbito global (como está aquí).

// (Opcional) Podrías también cerrar el modal al guardar, etc. Pero lo esencial es lo anterior.

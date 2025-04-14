
        $(document).ready(function() {
            $('#Contenido').DataTable({
                responsive: true,
                order: [[0, 'desc']],
                "language": {
                    "lengthMenu": "Mostrar _MENU_ registros por página",
                    "zeroRecords": "No se encontraron resultados",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                    "infoFiltered": "(filtrado de _MAX_ registros totales)",
                    "search": "Buscar:"
                }
            });
            $('#Contenido').css('visibility', 'visible');
        });


        function editarManga(manga) {
            console.log(manga); // Verifica que manga.titulo existe y contiene el valor correcto
            $('#manga_id').val(manga.id);
            $('#titulo_editar').val(manga.titulo);
            $('#autor_editar').val(manga.autor_id);
            $('#dibujante_editar').val(manga.dibujante_id);

            // Limpiar previamente las casillas de géneros y marcarlas según el manga
            $('.genero-checkbox').prop('checked', false);
            manga.generos.forEach(function(genero) {
                $('#genero_editar' + genero.id).prop('checked', true);
            });

            // Configurar el checkbox de edición según el estado de publicación
            $('#en_publicacion_editar').prop('checked', manga.en_publicacion);

            // Configurar la acción del formulario de edición
            $('#formEditar').attr('action', '/mangas/' + manga.id);
        }


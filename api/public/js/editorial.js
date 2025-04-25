
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
                }
            });
            $('#Contenido').css('visibility', 'visible');
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

        // Función para configurar la eliminación de la editorial
        function configurarEliminar(id) {
            $('#formEliminar').attr('action', '/editoriales/' + id);
        }

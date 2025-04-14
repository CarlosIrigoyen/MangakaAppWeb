 $(document).ready(function(){
            // Manejo de la visibilidad de los selects según el radio seleccionado
            $('.filter-radio').on('change', function(){
                var filterType = $(this).val();
                $('.filter-select').hide().prop('disabled', true);
                $('#filterSelectContainer').show();
                if(filterType == 'idioma'){
                    $('#select-idioma').show().prop('disabled', false);
                } else if(filterType == 'autor'){
                    $('#select-autor').show().prop('disabled', false);
                } else if(filterType == 'manga'){
                    $('#select-manga').show().prop('disabled', false);
                } else if(filterType == 'editorial'){
                    $('#select-editorial').show().prop('disabled', false);
                }
            });

            var currentFilter = "{{ request()->get('filter_type') }}";
            if(currentFilter){
                $('.filter-radio[value="'+currentFilter+'"]').prop('checked', true);
                $('#filterSelectContainer').show();
                if(currentFilter == 'idioma'){
                    $('#select-idioma').show().prop('disabled', false);
                } else if(currentFilter == 'autor'){
                    $('#select-autor').show().prop('disabled', false);
                } else if(currentFilter == 'manga'){
                    $('#select-manga').show().prop('disabled', false);
                } else if(currentFilter == 'editorial'){
                    $('#select-editorial').show().prop('disabled', false);
                }
            }

            // Variable con los datos para calcular el siguiente número de tomo y la fecha mínima (para el modal de creación).
            var nextTomos = @json($nextTomos);
            $('#manga_id').on('change', function(){
                var mangaId = $(this).val();
                var today = new Date().toISOString().split("T")[0];
                if(mangaId && nextTomos[mangaId]){
                    var data = nextTomos[mangaId];
                    $('#numero_tomo').val(data.numero);
                    if(data.numero > 1){
                        $('#fecha_publicacion').val(data.fecha);
                        $('#fecha_publicacion').attr('min', data.fecha);
                        $('#fecha_publicacion').attr('max', today);
                    } else {
                        $('#numero_tomo').val(1);
                        $('#fecha_publicacion').val('');
                        $('#fecha_publicacion').removeAttr('min');
                        $('#fecha_publicacion').attr('max', today);
                    }
                } else {
                    $('#numero_tomo').val('');
                    $('#fecha_publicacion').val('');
                    $('#fecha_publicacion').removeAttr('min');
                    $('#fecha_publicacion').attr('max', today);
                }
            });
        });
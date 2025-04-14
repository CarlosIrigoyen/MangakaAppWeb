#!/bin/bash

# Ruta de la carpeta principal
main_folder="/home/carlos/Documentos/Proyectos/MangakaBakaShop/api/public/vendor/adminlte/dist/img/Portadas"

# Recorre cada subcarpeta dentro de la carpeta principal
for subfolder in "$main_folder"/*/; do
    if [ -d "$subfolder" ]; then
        echo "Procesando subcarpeta: $subfolder"
        count=1
        # Recorre cada archivo en la subcarpeta
        for file in "$subfolder"*; do
            # Verifica si el archivo es una imagen con extensiones comunes
            if [[ -f "$file" && "$file" =~ \.(jpg|jpeg|png|gif|bmp|tiff)$ ]]; then
                # Renombra y cambia la extensión a .png
                new_name=$(printf "Portada%02d.png" "$count")
                mv "$file" "$subfolder$new_name"
                echo "Renombrado: $file -> $new_name"
                count=$((count + 1))
            fi
        done
    fi
done

echo "Renombrado completado."

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manga extends Model
{
    use HasFactory;
    protected $table = 'mangas'; // Asegurar el nombre correcto de la tabla
    // Definir los campos que pueden ser asignados masivamente
    protected $fillable = ['titulo', 'autor_id', 'dibujante_id', 'en_publicacion'];

    /**
     * Relación con el modelo Autor
     * Un manga pertenece a un autor.
     */
    public function autor()
    {
        return $this->belongsTo(Autor::class, 'autor_id');
    }

    /**
     * Relación con el modelo Dibujante
     * Un manga pertenece a un dibujante.
     */
    public function dibujante()
    {
        return $this->belongsTo(Dibujante::class, 'dibujante_id');
    }

    /**
     * Relación muchos a muchos con el modelo Genero
     * Un manga puede tener muchos géneros y un género puede estar asociado a muchos mangas.
     */
    public function generos(){
        return $this->belongsToMany(Genero::class, 'manga_genero', 'manga_id', 'genero_id');
    }
}

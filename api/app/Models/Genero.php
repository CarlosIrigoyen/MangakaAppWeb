<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Genero extends Model
{
    use HasFactory;

    protected $table = 'generos';
    protected $fillable = ['nombre'];

    /**
     * Relación muchos a muchos con Manga.
     * Indicamos la tabla pivote real 'manga_genero' y las claves foráneas.
     */
    public function mangas()
    {
        return $this->belongsToMany(
            Manga::class,     // Modelo relacionado
            'manga_genero',   // Nombre de la tabla pivote
            'genero_id',      // Clave foránea de este modelo en la pivote
            'manga_id'        // Clave foránea del modelo Manga en la pivote
        );
    }
}

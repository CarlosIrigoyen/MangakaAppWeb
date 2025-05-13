<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manga extends Model
{
    use HasFactory;

    protected $table = 'mangas';
    protected $fillable = [
        'titulo',
        'autor_id',
        'dibujante_id',
        'en_publicacion',
    ];

    /**
     * Un manga pertenece a un autor.
     */
    public function autor()
    {
        return $this->belongsTo(Autor::class, 'autor_id');
    }

    /**
     * Un manga pertenece a un dibujante.
     */
    public function dibujante()
    {
        return $this->belongsTo(Dibujante::class, 'dibujante_id');
    }

    /**
     * Relación muchos a muchos con Genero.
     * Indicamos la tabla pivote 'manga_genero' y sus claves foráneas.
     */
    public function generos()
    {
        return $this->belongsToMany(
            Genero::class,    // Modelo relacionado
            'manga_genero',   // Tabla pivote
            'manga_id',       // Clave foránea de este modelo en la pivote
            'genero_id'       // Clave foránea del modelo Genero en la pivote
        );
    }
}

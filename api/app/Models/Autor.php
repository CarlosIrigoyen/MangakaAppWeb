<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Autor extends Model
{
    use HasFactory;

    protected $table = 'autores';

    // Agregamos los nuevos campos para asignación masiva
    protected $fillable = ['nombre', 'apellido', 'fecha_nacimiento', 'activo'];

    /**
     * Relación uno a muchos con el modelo Manga.
     * Un autor puede tener muchos mangas.
     */
    public function mangas()
    {
        return $this->hasMany(Manga::class, 'autor_id');
    }
}


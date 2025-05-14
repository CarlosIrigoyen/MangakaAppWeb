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
        'activo',
    ];

    /** Scope para mangas activos */
    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    /** Scope para mangas inactivos */
    public function scopeInactivo($query)
    {
        return $query->where('activo', false);
    }

    public function autor()
    {
        return $this->belongsTo(Autor::class, 'autor_id');
    }

    public function dibujante()
    {
        return $this->belongsTo(Dibujante::class, 'dibujante_id');
    }

    public function generos()
    {
        return $this->belongsToMany(
            Genero::class,
            'manga_genero',
            'manga_id',
            'genero_id'
        );
    }
}

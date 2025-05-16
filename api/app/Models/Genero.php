<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Genero extends Model
{
    use HasFactory;

    protected $table = 'generos';

    protected $fillable = [
        'nombre',
        'activo',
    ];

    /**
     * Scope para géneros activos
     */
    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para géneros inactivos
     */
    public function scopeInactivo($query)
    {
        return $query->where('activo', false);
    }

    /**
     * Relación muchos a muchos con Manga.
     */
    public function mangas()
    {
        return $this->belongsToMany(
            Manga::class,
            'manga_genero',
            'genero_id',
            'manga_id'
        );
    }
}

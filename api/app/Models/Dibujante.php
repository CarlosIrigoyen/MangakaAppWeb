<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dibujante extends Model
{
    use HasFactory;

    protected $table = 'dibujantes';

    // Se agregan los campos nuevos al arreglo fillable
    protected $fillable = [
        'nombre',
        'apellido',
        'fecha_nacimiento',
        'activo',
    ];

    /**
     * Scope para dibujantes activos
     */
    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para dibujantes inactivos
     */
    public function scopeInactivo($query)
    {
        return $query->where('activo', false);
    }

    /**
     * Relación uno a muchos con el modelo Manga.
     * Un dibujante puede tener muchos mangas.
     */
    public function mangas()
    {
        return $this->hasMany(Manga::class, 'dibujante_id');
    }
}

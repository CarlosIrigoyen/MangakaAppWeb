<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Editorial extends Model
{
    use HasFactory;

    protected $table = 'editoriales';

    protected $fillable = [
        'nombre',
        'pais',
        'activo',
    ];

    /**
     * Scope para editoriales activas
     */
    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para editoriales inactivas
     */
    public function scopeInactivo($query)
    {
        return $query->where('activo', false);
    }

    /**
     * Relación tomos ⇄ editoriales (muchos a muchos).
     */
       public function tomos()
    {
        return $this->hasMany(Tomo::class, 'editorial_id');
    }
}

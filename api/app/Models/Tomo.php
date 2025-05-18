<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Tomo extends Model
{
    protected $table = 'tomos';

    protected $fillable = [
        'manga_id',
        'editorial_id',
        'numero_tomo',
        'formato',
        'idioma',
        'precio',
        'fecha_publicacion',
        'portada',
        'stock',
        'public_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Scope global: sólo trae tomos con tomos.activo = true
     */
    protected static function booted()
    {
        static::addGlobalScope('activo', function (Builder $builder) {
            $builder->where('tomos.activo', true);
        });
    }

    public function manga()
    {
        return $this->belongsTo(Manga::class);
    }

    public function editorial()
    {
        return $this->belongsTo(Editorial::class);
    }
}

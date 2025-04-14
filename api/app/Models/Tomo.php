<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tomo extends Model
{
    // Definir la tabla explícitamente (opcional si Laravel ya la detecta correctamente)
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
        'stock'
    ];

    // Si prefieres que Laravel maneje las marcas de tiempo, puedes quitar esta línea
    public $timestamps = false;

    // Relación: un Tomo pertenece a un Manga.
    public function manga()
    {
        return $this->belongsTo(Manga::class, 'manga_id');
    }

    // Relación: un Tomo pertenece a una Editorial.
    public function editorial()
    {
        return $this->belongsTo(Editorial::class, 'editorial_id');
    }
}

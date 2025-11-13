<?php
// app/Models/ClienteMangaSuscripcion.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteMangaSuscripcion extends Model
{
    use HasFactory;

    protected $table = 'cliente_manga_suscripciones';

    protected $fillable = [
        'cliente_id',
        'manga_id',
        'fcm_token',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function manga()
    {
        return $this->belongsTo(Manga::class);
    }
}

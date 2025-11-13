<?php
// app/Models/Cliente.php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Cliente extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'nombre',
        'email',
        'password',
        'direccion',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function facturas()
    {
        return $this->hasMany(Factura::class);
    }

    public function carritos()
    {
        return $this->hasMany(Carrito::class);
    }

    // Agregar relación con suscripciones
    public function suscripciones()
    {
        return $this->hasMany(ClienteMangaSuscripcion::class);
    }

    // Obtener mangas suscritos
    public function mangasSuscritos()
    {
        return $this->belongsToMany(Manga::class, 'cliente_manga_suscripciones', 'cliente_id', 'manga_id')
                    ->withTimestamps();
    }
}

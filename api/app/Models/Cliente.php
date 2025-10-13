<?php

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

    // Relación con facturas
    public function facturas()
    {
        return $this->hasMany(Factura::class);
    }

    // AGREGAR ESTA RELACIÓN CON EL CARRITO
    public function carritos()
    {
        return $this->hasMany(Carrito::class);
    }
}

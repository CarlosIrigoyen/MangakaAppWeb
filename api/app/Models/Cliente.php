<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Cliente extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = ['nombre', 'email', 'password'];

    // Opcional: ocultar campos sensibles en las respuestas JSON
    protected $hidden = ['password', 'remember_token'];
}

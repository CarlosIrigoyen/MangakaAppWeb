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
        'google_id', // NUEVO: Para login con Google
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_id', // Ocultamos el google_id en respuestas JSON
    ];

    // Método para encontrar cliente por Google ID
    public static function findByGoogleId($googleId)
    {
        return static::where('google_id', $googleId)->first();
    }

    // Método para encontrar o crear por Google
    public static function findOrCreateByGoogle($googleData)
    {
        $cliente = static::where('email', $googleData['email'])
                        ->orWhere('google_id', $googleData['google_id'])
                        ->first();

        if (!$cliente) {
            $cliente = static::create([
                'nombre' => $googleData['name'],
                'email' => $googleData['email'],
                'google_id' => $googleData['google_id'],
                'password' => bcrypt(uniqid()), // Password aleatorio
                'direccion' => 'Por definir - Actualiza tu perfil',
            ]);
        } elseif (empty($cliente->google_id)) {
            // Si ya existía por email pero no tenía google_id, lo actualizamos
            $cliente->update(['google_id' => $googleData['google_id']]);
        }

        return $cliente;
    }

    // Relaciones existentes (mantener)
    public function facturas()
    {
        return $this->hasMany(Factura::class);
    }

    public function carritos()
    {
        return $this->hasMany(Carrito::class);
    }

    public function suscripciones()
    {
        return $this->hasMany(ClienteMangaSuscripcion::class);
    }

    public function mangasSuscritos()
    {
        return $this->belongsToMany(Manga::class, 'cliente_manga_suscripciones', 'cliente_id', 'manga_id')
                    ->withTimestamps();
    }
}

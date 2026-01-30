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
        'google_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
    ];

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

    // NUEVO: dispositivos
    public function dispositivos()
    {
        return $this->hasMany(ClienteDispositivo::class);
    }

    // Helpers Google (si ya estaban)
    public static function findByGoogleId($googleId)
    {
        return static::where('google_id', $googleId)->first();
    }

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
                'password' => bcrypt(uniqid()),
                'direccion' => 'Por definir - Actualiza tu perfil',
            ]);
        } elseif (empty($cliente->google_id)) {
            $cliente->update(['google_id' => $googleData['google_id']]);
        }

        return $cliente;
    }
}

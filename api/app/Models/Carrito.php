<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Carrito extends Model
{
    use HasFactory;

    protected $table = 'carritos';

    protected $fillable = [
        'cliente_id',
        'tomo_id',
        'cantidad'
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function tomo()
    {
        return $this->belongsTo(Tomo::class);
    }
}

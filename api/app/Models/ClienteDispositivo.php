<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteDispositivo extends Model
{
    use HasFactory;

    protected $table = 'cliente_dispositivos';

    protected $fillable = [
        'cliente_id',
        'fcm_token',
        'platform',
        'last_active_at',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
}

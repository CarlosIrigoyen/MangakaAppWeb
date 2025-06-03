<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $fillable = ['cliente_id', 'total'];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function factura()
    {
        return $this->hasOne(Factura::class);
    }

    public function detalles()
    {
        return $this->hasMany(DetalleVenta::class);
    }
}

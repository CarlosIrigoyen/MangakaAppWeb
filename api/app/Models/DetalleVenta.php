<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleVenta extends Model
{
    protected $table = 'detalle_venta';
    protected $fillable = ['venta_id', 'tomo_id', 'cantidad', 'precio_unitario'];

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function tomo()
    {
        return $this->belongsTo(Tomo::class);
    }
}

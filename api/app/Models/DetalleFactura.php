<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleFactura extends Model
{
    protected $table = 'factura_detalle';
    protected $fillable = ['factura_id', 'tomo_id', 'cantidad', 'precio_unitario', 'subtotal'];

    public function factura()
    {
        return $this->belongsTo(Factura::class);
    }

    public function tomo()
    {
        return $this->belongsTo(Tomo::class);
    }
}

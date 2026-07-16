<?php

namespace App\Models;

use App\Models\Concerns\TieneCobros;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VentaPosItem extends Model
{
    use HasFactory, TieneCobros;

    protected $table = 'venta_pos_items';

    protected $fillable = [
        'venta_pos_id',
        'tipo',
        'vendible_type',
        'vendible_id',
        'cobro_id',
        'cantidad',
        'precio_unitario',
        'subtotal',
        'descripcion',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    /** Requerido por TieneCobros: para líneas oración/arte/otro el item es el cobrable. */
    public function totalAdeudado(): float
    {
        return (float) $this->subtotal;
    }

    public function ventaPos()
    {
        return $this->belongsTo(VentaPos::class, 'venta_pos_id');
    }

    public function vendible()
    {
        return $this->morphTo();
    }

    public function cobro()
    {
        return $this->belongsTo(Cobro::class, 'cobro_id');
    }
}

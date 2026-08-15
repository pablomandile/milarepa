<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Precio de un servicio de actividad (grabación/hospedaje/comida/transporte)
 * en una moneda NO principal. El precio en la moneda principal vive en la
 * columna plana del servicio (`valor`/`precio`).
 */
class ServicioPrecio extends Model
{
    use HasFactory;

    protected $table = 'servicio_precios';

    protected $fillable = [
        'servicioable_type',
        'servicioable_id',
        'moneda_id',
        'precio',
        'botonpago_id',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
    ];

    public function servicioable()
    {
        return $this->morphTo();
    }

    public function moneda()
    {
        return $this->belongsTo(Moneda::class, 'moneda_id');
    }

    public function botonPago()
    {
        return $this->belongsTo(BotonPago::class, 'botonpago_id');
    }
}

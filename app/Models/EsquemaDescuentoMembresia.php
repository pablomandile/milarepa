<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BotonPago;

class EsquemaDescuentoMembresia extends Model
{
    use HasFactory;

    protected $table = 'esquema_descuento_membresias';

    protected $fillable = [
        'esquema_descuento_id',
        'membresia_id',
        'botonpago_id',
        'moneda_id',
        'precio',
    ];

    public function esquemaDescuento()
    {
        return $this->belongsTo(EsquemaDescuento::class, 'esquema_descuento_id');
    }

    public function membresia()
    {
        return $this->belongsTo(Membresia::class, 'membresia_id');
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

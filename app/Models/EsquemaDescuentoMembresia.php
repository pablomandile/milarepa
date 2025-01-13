<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EsquemaDescuentoMembresia extends Model
{
    use HasFactory;

    protected $table = 'esquema_descuento_membresias';

    protected $fillable = [
        'esquema_descuento_id',
        'membresia_id',
        'moneda_id',
        'precio',
    ];

    public function esquemaDescuento()
    {
        return $this->belongsTo(EsquemaPrecio::class, 'esquema_descuento_id');
    }

    public function membresia()
    {
        return $this->belongsTo(Membresia::class, 'membresia_id');
    }

    public function moneda()
    {
        return $this->belongsTo(Moneda::class, 'moneda_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EsquemaPrecioMembresia extends Model
{
    use HasFactory;

    protected $table = 'esquema_precio_membresias';

    protected $fillable = [
        'esquema_precio_id',
        'membresia_id',
        'moneda_id',
        'precio',
    ];

    public function esquemaPrecio()
    {
        return $this->belongsTo(EsquemaPrecio::class, 'esquema_precio_id');
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

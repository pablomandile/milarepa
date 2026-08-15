<?php

namespace App\Models;

use App\Models\Concerns\TienePreciosPorMoneda;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BotonPago;

class Transporte extends Model
{
    use HasFactory, TienePreciosPorMoneda;

    protected $table = 'transportes';

    protected $fillable = [
        'descripcion',
        'botonpago_id',
        'precio'
    ];

    protected $casts = [
        'precio' => 'decimal:2',
    ];

    public function botonPago()
    {
        return $this->belongsTo(BotonPago::class, 'botonpago_id');
    }
}

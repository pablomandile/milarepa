<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\MetodoPago;

class BotonPago extends Model
{
    use HasFactory;

    protected $table = 'botones_pago';

    protected $fillable = [
        'nombre',
        'descripcion',
        'link',
        'metodo_pago_id',
    ];

    public function metodoPago()
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }
}

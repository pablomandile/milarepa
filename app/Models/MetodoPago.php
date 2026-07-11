<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MetodoPago extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'metodos_pago';

    protected $fillable = [
        'nombre', 
        'descripcion',
        'tipo_de_pago',
        'imagen_id',
    ];

    public function imagen()
    {
        return $this->belongsTo(Imagen::class, 'imagen_id');
    }

    public function botonesPago()
    {
        return $this->hasMany(BotonPago::class, 'metodo_pago_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BotonPago;

class Membresia extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre',
        'descripcion',
        'info',
        'entidad_id',
        'botonpago_id',
        'imagen_id',
        'valor'
    ];

    protected $casts = [
        'valor' => 'decimal:2'
    ];

    public function entidad()
    {
        return $this->belongsTo(Entidad::class, 'entidad_id');
    }

    public function botonPago()
    {
        return $this->belongsTo(BotonPago::class, 'botonpago_id');
    }

    public function imagen()
    {
        return $this->belongsTo(Imagen::class, 'imagen_id');
    }

    public function estadoCuenta()
    {
        return $this->hasMany(EstadoCuentaMembresia::class, 'membresia_id');
    }

    public function esquemaPrecioMembresias()
    {
        return $this->hasMany(EsquemaPrecioMembresia::class, 'membresia_id');
    }

}

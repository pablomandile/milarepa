<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Membresia extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'entidad_id',
        'valor'
    ];

    protected $casts = [
        'valor' => 'decimal:2'
    ];

    public function entidad()
    {
        return $this->belongsTo(Entidad::class, 'entidad_id');
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

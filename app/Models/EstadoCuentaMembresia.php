<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoCuentaMembresia extends Model
{
    use HasFactory;

    protected $table = 'estado_cuenta_membresias';

    protected $fillable = [
        'user_id',
        'membresia_id',
        'fecha_pago',
        'mes_pagado',
        'importe',
        'observaciones',
        'pagado',
        'estado',
        'modo',
        'comprobante'
    ];

    protected $casts = [
        'pagado' => 'boolean',
        'fecha_pago' => 'date',
    ];

    public const ESTADO_ACTIVA = 'Activa';
    public const ESTADO_EXPIRADA = 'Expirada';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function membresia()
    {
        return $this->belongsTo(Membresia::class, 'membresia_id');
    }
}

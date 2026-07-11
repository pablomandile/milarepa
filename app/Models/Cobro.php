<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cobro extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cobros';

    protected $fillable = [
        'cobrable_type',
        'cobrable_id',
        'monto',
        'moneda_id',
        'fecha_pago',
        'metodo_pago_id',
        'referencia',
        'comprobante_id',
        'observaciones',
        'registrado_por',
        'origen',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_pago' => 'date',
    ];

    public function cobrable()
    {
        return $this->morphTo();
    }

    public function moneda()
    {
        return $this->belongsTo(Moneda::class, 'moneda_id');
    }

    public function metodoPago()
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }

    public function comprobante()
    {
        return $this->belongsTo(Imagen::class, 'comprobante_id');
    }

    public function registrador()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}

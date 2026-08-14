<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cobro extends Model
{
    use HasFactory, SoftDeletes;

    // Ciclo de vida: confirmado = plata verificada; a_revisar = comprobante
    // informado por el usuario, pendiente de verificación (no suma al saldo).
    public const ESTADO_CONFIRMADO = 'confirmado';
    public const ESTADO_A_REVISAR = 'a_revisar';

    protected $table = 'cobros';

    protected $fillable = [
        'cobrable_type',
        'cobrable_id',
        'monto',
        'moneda_id',
        'fecha_pago',
        'metodo_pago_id',
        'referencia',
        'observaciones',
        'registrado_por',
        'origen',
        'estado',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_pago' => 'date',
    ];

    protected $attributes = [
        'estado' => self::ESTADO_CONFIRMADO,
    ];

    public function scopeConfirmados($query)
    {
        return $query->where('estado', self::ESTADO_CONFIRMADO);
    }

    public function scopeARevisar($query)
    {
        return $query->where('estado', self::ESTADO_A_REVISAR);
    }

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

    public function comprobantes()
    {
        return $this->hasMany(CobroComprobante::class);
    }

    public function registrador()
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}

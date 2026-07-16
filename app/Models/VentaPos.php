<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VentaPos extends Model
{
    use HasFactory;

    protected $table = 'venta_pos';

    protected $fillable = [
        'fecha',
        'vendedor_id',
        'cliente_user_id',
        'entidad_id',
        'metodo_pago_id',
        'comprobante_id',
        'total',
        'observaciones',
        'idempotency_key',
    ];

    protected $casts = [
        'fecha' => 'date',
        'total' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(VentaPosItem::class, 'venta_pos_id');
    }

    public function vendedor()
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    public function cliente()
    {
        return $this->belongsTo(User::class, 'cliente_user_id');
    }

    public function entidad()
    {
        return $this->belongsTo(Entidad::class, 'entidad_id');
    }

    public function metodoPago()
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }

    public function comprobante()
    {
        return $this->belongsTo(Imagen::class, 'comprobante_id');
    }
}

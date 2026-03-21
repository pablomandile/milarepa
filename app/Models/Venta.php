<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    use HasFactory;

    protected $table = 'ventas';

    protected $fillable = [
        'fecha',
        'entidad_id',
        'libro_id',
        'cantidad',
        'precio_unitario',
        'montoTotal',
        'modo',
        'comprobante_id',
        'vendedor_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'entidad_id' => 'integer',
        'libro_id' => 'integer',
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'montoTotal' => 'decimal:2',
        'comprobante_id' => 'integer',
        'vendedor_id' => 'integer',
    ];

    public function entidad()
    {
        return $this->belongsTo(Entidad::class, 'entidad_id');
    }

    public function libro()
    {
        return $this->belongsTo(Libro::class, 'libro_id');
    }

    public function comprobante()
    {
        return $this->belongsTo(Imagen::class, 'comprobante_id');
    }

    public function vendedor()
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }
}

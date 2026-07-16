<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InscripcionClaseItem extends Model
{
    use HasFactory;

    protected $table = 'inscripcion_clase_items';

    protected $fillable = [
        'inscripcion_clase_id',
        'categoria',
        'producto_type',
        'producto_id',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function inscripcionClase()
    {
        return $this->belongsTo(InscripcionClase::class, 'inscripcion_clase_id');
    }

    public function producto()
    {
        return $this->morphTo();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarioEntidadOracion extends Model
{
    use HasFactory;

    protected $table = 'inventario_entidad_oracion';

    protected $fillable = [
        'entidad_id',
        'oracion_id',
        'cantidad',
    ];

    protected $casts = [
        'entidad_id' => 'integer',
        'oracion_id' => 'integer',
        'cantidad' => 'integer',
    ];

    public function entidad()
    {
        return $this->belongsTo(Entidad::class, 'entidad_id');
    }

    public function oracion()
    {
        return $this->belongsTo(Oracion::class, 'oracion_id');
    }
}

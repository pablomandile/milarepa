<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarioEntidadOtro extends Model
{
    use HasFactory;

    protected $table = 'inventario_entidad_otro';

    protected $fillable = [
        'entidad_id',
        'otro_id',
        'cantidad',
    ];

    protected $casts = [
        'entidad_id' => 'integer',
        'otro_id' => 'integer',
        'cantidad' => 'integer',
    ];

    public function entidad()
    {
        return $this->belongsTo(Entidad::class, 'entidad_id');
    }

    public function otro()
    {
        return $this->belongsTo(Otro::class, 'otro_id');
    }
}

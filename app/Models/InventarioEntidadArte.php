<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarioEntidadArte extends Model
{
    use HasFactory;

    protected $table = 'inventario_entidad_arte';

    protected $fillable = [
        'entidad_id',
        'arte_id',
        'cantidad',
    ];

    protected $casts = [
        'entidad_id' => 'integer',
        'arte_id' => 'integer',
        'cantidad' => 'integer',
    ];

    public function entidad()
    {
        return $this->belongsTo(Entidad::class, 'entidad_id');
    }

    public function arte()
    {
        return $this->belongsTo(Arte::class, 'arte_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarioEntidadLibro extends Model
{
    use HasFactory;

    protected $table = 'inventario_entidad_libro';

    protected $fillable = [
        'entidad_id',
        'libro_id',
        'cantidad',
    ];

    protected $casts = [
        'entidad_id' => 'integer',
        'libro_id' => 'integer',
        'cantidad' => 'integer',
    ];

    public function entidad()
    {
        return $this->belongsTo(Entidad::class, 'entidad_id');
    }

    public function libro()
    {
        return $this->belongsTo(Libro::class, 'libro_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarioEntidadArticuloTienda extends Model
{
    use HasFactory;

    protected $table = 'inventario_entidad_articulo_tienda';

    protected $fillable = [
        'entidad_id',
        'articulo_tienda_id',
        'cantidad',
    ];

    protected $casts = [
        'entidad_id' => 'integer',
        'articulo_tienda_id' => 'integer',
        'cantidad' => 'integer',
    ];

    public function entidad()
    {
        return $this->belongsTo(Entidad::class, 'entidad_id');
    }

    public function articuloTienda()
    {
        return $this->belongsTo(ArticuloTienda::class, 'articulo_tienda_id');
    }
}

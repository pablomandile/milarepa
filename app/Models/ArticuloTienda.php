<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArticuloTienda extends Model
{
    use HasFactory;

    protected $table = 'articulos_tienda';

    protected $fillable = [
        'categoria_tienda_id',
        'titulo',
        'descripcion',
        'imagen_id',
        'precio',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
    ];

    public function categoria()
    {
        return $this->belongsTo(CategoriaTienda::class, 'categoria_tienda_id');
    }

    public function imagen()
    {
        return $this->belongsTo(Imagen::class, 'imagen_id');
    }

    public function inventariosEntidad()
    {
        return $this->hasMany(InventarioEntidadArticuloTienda::class, 'articulo_tienda_id');
    }
}

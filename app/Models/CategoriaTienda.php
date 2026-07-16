<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoriaTienda extends Model
{
    use HasFactory;

    protected $table = 'categorias_tienda';

    protected $fillable = [
        'nombre',
        'orden',
    ];

    protected $casts = [
        'orden' => 'integer',
    ];

    public function articulos()
    {
        return $this->hasMany(ArticuloTienda::class, 'categoria_tienda_id');
    }
}

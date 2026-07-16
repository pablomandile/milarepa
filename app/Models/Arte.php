<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Arte extends Model
{
    use HasFactory;

    protected $table = 'arte';

    /** Formatos posibles (categoría "Arte" de la tienda Tharpa). */
    public const TIPOS = ['Tarjeta A4', 'Tarjeta A5', 'Tarjeta A6', 'Tarjeta A7', 'Tarjeta Cuadrada'];

    protected $fillable = [
        'titulo',
        'descripcion',
        'tipo',
        'imagen_id',
        'precio',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
    ];

    public function imagen()
    {
        return $this->belongsTo(Imagen::class, 'imagen_id');
    }

    public function inventariosEntidad()
    {
        return $this->hasMany(InventarioEntidadArte::class, 'arte_id');
    }
}

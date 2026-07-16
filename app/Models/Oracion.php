<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Oracion extends Model
{
    use HasFactory;

    protected $table = 'oraciones';

    /** Formatos posibles (categoría "Oraciones" de la tienda Tharpa). */
    public const TIPOS = ['Librillo', 'Audio'];

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
        return $this->hasMany(InventarioEntidadOracion::class, 'oracion_id');
    }
}

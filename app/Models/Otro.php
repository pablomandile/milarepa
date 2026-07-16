<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Otro extends Model
{
    use HasFactory;

    protected $table = 'otros';

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
        return $this->hasMany(InventarioEntidadOtro::class, 'otro_id');
    }
}

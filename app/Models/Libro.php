<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Libro extends Model
{
    use HasFactory;

    protected $table = 'libros';

    protected $fillable = [
        'titulo',
        'descripcion',
        'isbn',
        'autor',
        'editorial',
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

    public function inventarioLibro()
    {
        return $this->hasOne(InventarioLibro::class, 'libro_id');
    }
}

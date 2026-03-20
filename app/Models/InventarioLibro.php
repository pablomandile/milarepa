<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarioLibro extends Model
{
    use HasFactory;

    protected $table = 'inventario_libros';

    protected $fillable = [
        'libro_id',
        'cantidad',
    ];

    public function libro()
    {
        return $this->belongsTo(Libro::class, 'libro_id');
    }
}

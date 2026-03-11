<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaginaActividadOnline extends Model
{
    use HasFactory;

    protected $table = 'paginas_actividades_online';

    protected $fillable = [
        'mes_referencia',
        'imagen_id',
    ];

    public function imagen()
    {
        return $this->belongsTo(Imagen::class, 'imagen_id');
    }
}


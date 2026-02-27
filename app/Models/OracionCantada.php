<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OracionCantada extends Model
{
    use HasFactory;

    protected $table = 'oraciones_cantadas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'dia',
        'dias_semana',
        'hora',
        'periodicidad',
        'imagen',
        'mostrar_en_calendario',
    ];

    protected $casts = [
        'dia' => 'integer',
        'dias_semana' => 'array',
        'mostrar_en_calendario' => 'boolean',
    ];
}

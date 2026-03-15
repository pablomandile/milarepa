<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailPlantilla extends Model
{
    protected $fillable = [
        'nombre',
        'descripcion',
        'plantilla_archivo',
    ];
}

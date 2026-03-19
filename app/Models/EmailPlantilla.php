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

    public static function listaPlantillasArchivo(): array
    {
        return static::query()
            ->whereNotNull('plantilla_archivo')
            ->where('plantilla_archivo', '!=', '')
            ->orderBy('plantilla_archivo')
            ->pluck('plantilla_archivo')
            ->unique()
            ->values()
            ->all();
    }
}

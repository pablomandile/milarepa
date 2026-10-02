<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maestro extends Model
{
    use HasFactory;
    
    protected $table = 'maestros';

    protected $fillable = [
        'nombre', 
        'telefono', 
        'email',
        'sobre_maestro',
        'imagen_id',
        'residente',
    ];

    protected $casts = [
        'residente' => 'boolean',
    ];

    public function scopeOrdenParaSelect($query)
    {
        return $query->orderByDesc('residente')->orderBy('nombre');
    }

    public function imagen()
    {
        return $this->belongsTo(Imagen::class, 'imagen_id');
    }
}

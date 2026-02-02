<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoActividad extends Model
{
    use HasFactory;

    protected $table = 'estados_actividad';

    protected $fillable = [
        'estado',
        'descripcion',
    ];

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'estado_id');
    }
}

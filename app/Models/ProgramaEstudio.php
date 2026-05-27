<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramaEstudio extends Model
{
    use HasFactory;

    protected $table = 'programa_estudios';

    protected $fillable = [
        'nombre',
        'abreviacion',
        'descripcion',
        'compromisos_pdf',
    ];

    public function programaGrabaciones()
    {
        return $this->hasMany(ProgramaGrabacion::class, 'programa_estudio_id');
    }
}

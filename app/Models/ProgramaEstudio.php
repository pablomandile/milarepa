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
        'descripcion'
    ];
}

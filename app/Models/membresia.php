<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Membresia extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'entidad_id'
    ];

    public function entidad()
    {
        return $this->belongsTo(Entidad::class, 'entidad_id');
    }

}

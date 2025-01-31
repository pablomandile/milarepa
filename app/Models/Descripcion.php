<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Descripcion extends Model
{
    use HasFactory;
    
    protected $table = 'descripciones';

    protected $fillable = [
        'nombre',
        'descripcion'
    ];
    
}

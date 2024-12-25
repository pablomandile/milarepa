<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comida extends Model
{
    use HasFactory;
    
    protected $table = 'comidas';

    protected $fillable = [
        'nombre', 
        'descripcion',
        'precio',
        'vegano', 
        'celiaco'
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcercaDe extends Model
{
    use HasFactory;
    
    protected $table = 'acerca_de';

    protected $fillable = [
        'descripcion'
    ];
    
}

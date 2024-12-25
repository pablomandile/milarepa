<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LugarHospedaje extends Model
{
    use HasFactory;
    
    protected $table = 'lugares_hospedaje';

    protected $fillable = [
        'nombre', 
        'descripcion',
        'direccion',
        'telefono',
        'email',
        'web'
    ];

    public function hospedajes()
    {
        return $this->hasMany(Hospedaje::class, 'lugar_hospedaje_id');
    }

}

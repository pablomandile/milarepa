<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BotonPago;

class Comida extends Model
{
    use HasFactory;
    
    protected $table = 'comidas';

    protected $fillable = [
        'nombre', 
        'descripcion',
        'botonpago_id',
        'precio',
        'vegano', 
        'celiaco'
    ];

    public function botonPago()
    {
        return $this->belongsTo(BotonPago::class, 'botonpago_id');
    }
}

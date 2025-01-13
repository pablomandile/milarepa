<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EsquemaPrecio extends Model
{
    use HasFactory;
    
    protected $table = 'esquema_precios';

    protected $fillable = ['nombre'];

    public function membresias()
    {

        return $this->hasMany(EsquemaPrecioMembresia::class, 'esquema_precio_id');
    }
}

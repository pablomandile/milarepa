<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EsquemaDescuento extends Model
{
    use HasFactory;
    
    protected $table = 'esquema_descuentos';

    protected $fillable = ['nombre'];

    public function membresias()
    {

        return $this->hasMany(EsquemaDescuentoMembresia::class, 'esquema_descuento_id');
    }

}

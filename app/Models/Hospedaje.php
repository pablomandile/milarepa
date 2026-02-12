<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BotonPago;

class Hospedaje extends Model
{
    use HasFactory;
    
    protected $table = 'hospedajes';

    protected $fillable = [
        'nombre', 
        'descripcion',
        'botonpago_id',
        'precio',
        'lugar_hospedaje_id'
    ];

    public function lugarHospedaje()
    {
        return $this->belongsTo(LugarHospedaje::class, 'lugar_hospedaje_id');
    }

    public function botonPago()
    {
        return $this->belongsTo(BotonPago::class, 'botonpago_id');
    }
}

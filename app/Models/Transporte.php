<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BotonPago;

class Transporte extends Model
{
    use HasFactory;
    
    protected $table = 'transportes';

    protected $fillable = [
        'descripcion',
        'botonpago_id',
        'precio'
    ];

    public function botonPago()
    {
        return $this->belongsTo(BotonPago::class, 'botonpago_id');
    }
}

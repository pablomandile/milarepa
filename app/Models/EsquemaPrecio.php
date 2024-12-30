<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EsquemaPrecio extends Model
{
    use HasFactory;
    
    protected $table = 'esquema_precios';

    protected $fillable = [
        'nombre', 
        'membresia_id',
        'precio',
        'moneda_id'
    ];
    
    public function membresia()
    {
        return $this->belongsTo(Membresia::class, 'membresia_id');
    }

    public function moneda()
    {
        return $this->belongsTo(Moneda::class, 'moneda_id');
    }
}

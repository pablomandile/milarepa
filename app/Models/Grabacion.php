<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BotonPago;

class Grabacion extends Model
{
    use HasFactory;
    
    protected $table = 'grabaciones';

    protected $fillable = [
        'nombre',
        'botonpago_id',
        'valor'
    ];

    protected $casts = [
        'valor' => 'decimal:2',
    ];
    
    public function linksgrabacion()
    {
        return $this->hasMany(LinkGrabacion::class);
    }

    public function botonPago()
    {
        return $this->belongsTo(BotonPago::class, 'botonpago_id');
    }
}

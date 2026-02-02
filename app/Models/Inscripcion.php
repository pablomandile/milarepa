<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inscripcion extends Model
{
    use HasFactory;

    protected $table = 'inscripciones';

    protected $fillable = [
        'actividad_id',
        'user_id',
        'membresia',
        'precioGeneral',
        'montoapagar',
        'pago',
        'estado_id',
        'envioLinkStream',
        'envioGrabación',
        'comprobante',
        'asistencia',
        'online',
        'hospedaje_id',
        'comida_id',
        'transporte_id',
    ];

    protected $casts = [
        'precioGeneral' => 'decimal:2',
        'montoapagar' => 'decimal:2',
        'online' => 'boolean',
    ];

    public function actividad()
    {
        return $this->belongsTo(Actividad::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function estado()
    {
        return $this->belongsTo(EstadoActividad::class, 'estado_id');
    }

    public function hospedaje()
    {
        return $this->belongsTo(Hospedaje::class);
    }

    public function comida()
    {
        return $this->belongsTo(Comida::class);
    }

    public function transporte()
    {
        return $this->belongsTo(Transporte::class);
    }
}

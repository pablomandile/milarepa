<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invitado extends Model
{
    use HasFactory;

    protected $table = 'invitados';

    protected $fillable = [
        'inscripcion_id',
        'nombre',
        'apellido',
        'telefono',
        'online',
        'asistencia',
        'incluye_grabacion',
        'montoActividad',
        'montoGrabacion',
        'montoComidas',
        'montoTransporte',
        'montoHospedaje',
        'montoapagar',
    ];

    protected $casts = [
        'online' => 'boolean',
        'incluye_grabacion' => 'boolean',
        'montoActividad' => 'decimal:2',
        'montoGrabacion' => 'decimal:2',
        'montoComidas' => 'decimal:2',
        'montoTransporte' => 'decimal:2',
        'montoHospedaje' => 'decimal:2',
        'montoapagar' => 'decimal:2',
    ];

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class);
    }

    public function comidas()
    {
        return $this->belongsToMany(Comida::class, 'invitado_comida', 'invitado_id', 'comida_id')
            ->withTimestamps();
    }

    public function transportes()
    {
        return $this->belongsToMany(Transporte::class, 'invitado_transporte', 'invitado_id', 'transporte_id')
            ->withTimestamps();
    }

    public function hospedajes()
    {
        return $this->belongsToMany(Hospedaje::class, 'invitado_hospedaje', 'invitado_id', 'hospedaje_id')
            ->withTimestamps();
    }
}

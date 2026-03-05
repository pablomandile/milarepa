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
        'estado',
        'precioGeneral',
        'montoActividad',
        'montoGrabacion',
        'montoTransporte',
        'montoComidas',
        'montoapagar',
        'pago',
        'envioLinkStream',
        'envioRegistro',
        'envioConfirmacion',
        'envioGrabacion',
        'asistencia',
        'online',
        'hospedaje_id',
        'comida_id',
        'transporte_id',
        'guest_user_id',
        'auditoria_fecha',
        'auditor',
    ];

    protected $casts = [
        'estado' => 'string',
        'precioGeneral' => 'decimal:2',
        'montoActividad' => 'decimal:2',
        'montoGrabacion' => 'decimal:2',
        'montoTransporte' => 'decimal:2',
        'montoComidas' => 'decimal:2',
        'montoapagar' => 'decimal:2',
        'online' => 'boolean',
        'auditoria_fecha' => 'datetime',
    ];

    public function actividad()
    {
        return $this->belongsTo(Actividad::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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

    public function guestUser()
    {
        return $this->belongsTo(GuestUser::class, 'guest_user_id');
    }

    public function auditorUser()
    {
        return $this->belongsTo(User::class, 'auditor');
    }

    public function comprobantes()
    {
        return $this->hasMany(InscripcionComprobante::class)->latest();
    }
}


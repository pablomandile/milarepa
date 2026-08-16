<?php

namespace App\Models;

use App\Models\Concerns\TieneCobros;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inscripcion extends Model
{
    use HasFactory, TieneCobros;

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
        'montoHospedaje',
        'montoapagar',
        'moneda_id',
        'monto_moneda_principal',
        'monto_invitados',
        'pago',
        'fecha_pago',
        'referencia_pago',
        'observaciones',
        'envioLinkStream',
        'envioRegistro',
        'envioConfirmacion',
        'envioGrabacion',
        'asistencia',
        'confirmado_manual',
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
        'montoHospedaje' => 'decimal:2',
        'montoapagar' => 'decimal:2',
        'monto_moneda_principal' => 'decimal:2',
        'monto_invitados' => 'decimal:2',
        'online' => 'boolean',
        'confirmado_manual' => 'boolean',
        'auditoria_fecha' => 'datetime',
        'fecha_pago' => 'date',
    ];

    public function totalAdeudado(): float
    {
        // Total dividido (BUSINESS_RULES §2.1bis): la deuda son LAS DOS porciones,
        // la de la moneda de la inscripción y la que quedó en la principal. Mirando
        // sólo `montoapagar`, una inscripción de "USD 120 + $ 2.000" se marcaba
        // Saldado al cobrar los 120 y los 2.000 desaparecían.
        //
        // La suma mezcla unidades a propósito: sin cotización es la única forma de
        // decidir Saldado / Parcial / Pendiente, y es el mismo criterio que ya usa
        // el checkout para resolver el estado. El desglose real vive en las dos
        // columnas, que son las que se muestran.
        return (float) $this->montoapagar + (float) ($this->monto_moneda_principal ?? 0);
    }

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

    public function moneda()
    {
        return $this->belongsTo(Moneda::class, 'moneda_id');
    }

    public function comidas()
    {
        return $this->belongsToMany(Comida::class, 'inscripcion_comida', 'inscripcion_id', 'comida_id')
            ->withTimestamps();
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

    public function invitados()
    {
        return $this->hasMany(Invitado::class);
    }
}


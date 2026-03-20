<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InscripcionClase extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'inscripciones_clases';

    protected $fillable = [
        'clase_id',
        'user_id',
        'nombre_snapshot',
        'email_snapshot',
        'membresia',
        'precioGeneral',
        'montoActividad',
        'montoTharpa',
        'montoTienda',
        'montoApagar',
        'pago',
        'online',
        'guest_user_id',
    ];

    protected $casts = [
        'precioGeneral' => 'decimal:2',
        'montoActividad' => 'decimal:2',
        'montoTharpa' => 'decimal:2',
        'montoTienda' => 'decimal:2',
        'montoApagar' => 'decimal:2',
        'online' => 'boolean',
    ];

    public function clase()
    {
        return $this->belongsTo(Clase::class, 'clase_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function guestUser()
    {
        return $this->belongsTo(GuestUser::class, 'guest_user_id');
    }
}

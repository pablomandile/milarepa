<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembresiaUsuario extends Model
{
    protected $table = 'membresia_usuario';

    protected $fillable = [
        'user_id',
        'membresia_id',
        'membresia_inscripcion_fecha',
        'membresia_online',
        'membresia_online_motivo',
        'info_tarjetas_kadampa',
        'envioInfoTk',
        'envioActOnline',
    ];

    protected $casts = [
        'membresia_online' => 'boolean',
        'info_tarjetas_kadampa' => 'boolean',
        'membresia_inscripcion_fecha' => 'date',
        'envioInfoTk' => 'datetime',
        'envioActOnline' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function membresia(): BelongsTo
    {
        return $this->belongsTo(Membresia::class, 'membresia_id');
    }
}

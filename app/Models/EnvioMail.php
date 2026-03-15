<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvioMail extends Model
{
    protected $table = 'envios_mails';

    protected $fillable = [
        'fecha',
        'tipo',
        'user_id',
        'destinatario',
        'motivo',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

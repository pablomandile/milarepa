<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Match evento→actividad recordado de importaciones multievento previas
 * (tabla temporal del paralelo de sistemas; descartable al finalizarlo).
 */
class MultieventoMapeo extends Model
{
    protected $table = 'multievento_mapeos';

    protected $fillable = [
        'clave',
        'nombre_evento',
        'fecha_evento',
        'actividad_id',
    ];

    protected $casts = [
        'fecha_evento' => 'date',
    ];

    public function actividad()
    {
        return $this->belongsTo(Actividad::class);
    }
}

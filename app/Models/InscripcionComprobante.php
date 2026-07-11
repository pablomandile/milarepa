<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InscripcionComprobante extends Model
{
    use HasFactory;

    protected $table = 'inscripcion_comprobantes';

    protected $fillable = [
        'inscripcion_id',
        'imagen_id',
        'descripcion',
    ];

    // Compat: la UI sigue leyendo `ruta` (path). Ahora sale de la imagen enlazada.
    protected $appends = ['ruta'];

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class);
    }

    public function imagen()
    {
        return $this->belongsTo(Imagen::class, 'imagen_id');
    }

    public function getRutaAttribute(): ?string
    {
        return $this->imagen?->ruta;
    }
}

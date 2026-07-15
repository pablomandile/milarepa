<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Comprobante de un cobro (1 cobro : N comprobantes). Apunta a una `Imagen`.
 * El accessor `ruta` mantiene el mismo shape que consumen las vistas Vue.
 */
class CobroComprobante extends Model
{
    protected $table = 'cobro_comprobantes';

    protected $fillable = [
        'cobro_id',
        'imagen_id',
        'descripcion',
    ];

    protected $appends = ['ruta'];

    public function cobro()
    {
        return $this->belongsTo(Cobro::class, 'cobro_id');
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

<?php

namespace App\Models\Concerns;

use App\Models\Cobro;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Da a un modelo la relación polimórfica con el ledger de cobros.
 * Cada modelo que lo use debe implementar totalAdeudado() devolviendo su columna de total.
 */
trait TieneCobros
{
    public function cobros(): MorphMany
    {
        return $this->morphMany(Cobro::class, 'cobrable');
    }

    public function montoCobrado(): float
    {
        return (float) $this->cobros()->sum('monto');
    }

    public function saldoPendiente(): float
    {
        return round($this->totalAdeudado() - $this->montoCobrado(), 2);
    }

    abstract public function totalAdeudado(): float;
}

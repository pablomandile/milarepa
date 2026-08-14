<?php

namespace App\Models\Concerns;

use App\Models\Cobro;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

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
        // Sólo cuenta plata verificada: los cobros a revisar no suman.
        return (float) $this->cobros()->confirmados()->sum('monto');
    }

    public function cobrosARevisar(): MorphMany
    {
        return $this->cobros()->aRevisar();
    }

    public function tieneCobrosARevisar(): bool
    {
        return $this->cobrosARevisar()->exists();
    }

    /**
     * Comprobantes de todos los cobros aplanados a [{ruta, descripcion, created_at}]
     * (shape que consumen las vistas de usuario). Requiere eager load de
     * `cobros.comprobantes.imagen` para no disparar N+1.
     */
    public function comprobantesDeCobros(): Collection
    {
        return $this->cobros
            ->flatMap(fn ($cobro) => $cobro->comprobantes)
            ->sortByDesc('created_at')
            ->map(fn ($comprobante) => [
                'ruta' => $comprobante->ruta,
                'descripcion' => $comprobante->descripcion,
                'created_at' => $comprobante->created_at,
            ])
            ->values();
    }

    public function saldoPendiente(): float
    {
        return round($this->totalAdeudado() - $this->montoCobrado(), 2);
    }

    abstract public function totalAdeudado(): float;
}

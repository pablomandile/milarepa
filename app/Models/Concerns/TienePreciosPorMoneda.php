<?php

namespace App\Models\Concerns;

use App\Models\Moneda;
use App\Models\ServicioPrecio;

/**
 * Precios multi-moneda para servicios de actividades. La columna plana del
 * servicio (`precio`, o la que indique campoPrecioPlano()) es el precio en la
 * moneda principal; las demás monedas viven en `servicio_precios`.
 */
trait TienePreciosPorMoneda
{
    public function precios()
    {
        return $this->morphMany(ServicioPrecio::class, 'servicioable');
    }

    /** Columna plana que guarda el precio en la moneda principal. */
    public function campoPrecioPlano(): string
    {
        return 'precio';
    }

    /**
     * Precio del servicio en la moneda pedida. null/principal → columna plana;
     * otra moneda → fila de servicio_precios, o null si no tiene precio en ella.
     */
    public function precioEnMoneda(?int $monedaId): ?float
    {
        if ($monedaId === null || $monedaId === Moneda::principalId()) {
            return (float) $this->{$this->campoPrecioPlano()};
        }

        $fila = $this->relationLoaded('precios')
            ? $this->precios->firstWhere('moneda_id', $monedaId)
            : $this->precios()->where('moneda_id', $monedaId)->first();

        return $fila !== null ? (float) $fila->precio : null;
    }

    /**
     * Accessor OPT-IN (no agregar a $appends: la grilla pública serializa todos
     * los servicios y no lo necesita). Shape que consume Pago.vue
     * (resolverPrecioItemEnMoneda): fila sintética de la moneda principal
     * (columna plana + botón plano) primero, luego las filas de servicio_precios.
     */
    public function getPreciosPorMonedaAttribute(): array
    {
        $principal = Moneda::principal();

        $filas = [];
        if ($principal) {
            $filas[] = [
                'moneda_id' => (int) $principal->id,
                'precio' => (float) $this->{$this->campoPrecioPlano()},
                'es_principal' => true,
                'moneda' => ['id' => (int) $principal->id, 'nombre' => $principal->nombre, 'simbolo' => $principal->simbolo],
                'botonpago' => $this->botonPago
                    ? ['id' => $this->botonPago->id, 'nombre' => $this->botonPago->nombre, 'link' => $this->botonPago->link]
                    : null,
            ];
        }

        $precios = $this->relationLoaded('precios')
            ? $this->precios
            : $this->precios()->with(['moneda', 'botonPago'])->get();

        foreach ($precios as $fila) {
            if ($principal && (int) $fila->moneda_id === (int) $principal->id) {
                continue; // defensa: la principal siempre sale de la columna plana
            }
            $filas[] = [
                'moneda_id' => (int) $fila->moneda_id,
                'precio' => (float) $fila->precio,
                'es_principal' => false,
                'moneda' => $fila->moneda
                    ? ['id' => (int) $fila->moneda->id, 'nombre' => $fila->moneda->nombre, 'simbolo' => $fila->moneda->simbolo]
                    : null,
                'botonpago' => $fila->botonPago
                    ? ['id' => $fila->botonPago->id, 'nombre' => $fila->botonPago->nombre, 'link' => $fila->botonPago->link]
                    : null,
            ];
        }

        return $filas;
    }
}

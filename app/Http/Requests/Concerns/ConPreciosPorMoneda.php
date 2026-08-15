<?php

namespace App\Http\Requests\Concerns;

/**
 * Reglas del array `precios` (precios del servicio en monedas no principales)
 * para los FormRequests de los ABMs de servicios de actividades.
 */
trait ConPreciosPorMoneda
{
    protected function reglasPrecios(): array
    {
        return [
            'precios' => ['nullable', 'array'],
            'precios.*.moneda_id' => ['required', 'integer', 'exists:monedas,id', 'distinct'],
            'precios.*.precio' => ['required', 'numeric', 'min:0'],
            'precios.*.botonpago_id' => ['nullable', 'exists:botones_pago,id'],
        ];
    }

    protected function mensajesPrecios(): array
    {
        return [
            'precios.*.moneda_id.required' => __('Elegí la moneda de cada precio adicional'),
            'precios.*.moneda_id.distinct' => __('No puede haber dos precios con la misma moneda'),
            'precios.*.precio.required' => __('El precio no puede quedar vacío'),
            'precios.*.precio.numeric' => __('El precio debe ser numérico'),
            'precios.*.precio.min' => __('El precio no puede ser negativo'),
        ];
    }
}

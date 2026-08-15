<?php

namespace App\Services;

use App\Models\Moneda;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Sincroniza las filas de servicio_precios de un servicio de actividad
 * (grabación/hospedaje/comida/transporte) desde el payload `precios` del ABM.
 */
class ServicioPrecioService
{
    /**
     * @param array $filas [['moneda_id' => int, 'precio' => num, 'botonpago_id' => ?int], ...]
     */
    public function sincronizar(Model $servicio, array $filas): void
    {
        // El precio en la moneda principal vive en la columna plana del
        // servicio: si llega una fila con esa moneda se descarta (defensa).
        $principalId = Moneda::principalId();
        $filas = array_values(array_filter(
            $filas,
            fn ($fila) => $principalId === null || (int) $fila['moneda_id'] !== $principalId
        ));

        DB::transaction(function () use ($servicio, $filas) {
            $servicio->precios()
                ->whereNotIn('moneda_id', array_map(fn ($fila) => (int) $fila['moneda_id'], $filas))
                ->delete();

            foreach ($filas as $fila) {
                $servicio->precios()->updateOrCreate(
                    ['moneda_id' => (int) $fila['moneda_id']],
                    ['precio' => $fila['precio'], 'botonpago_id' => $fila['botonpago_id'] ?? null]
                );
            }
        });
    }
}

<?php

namespace App\Services;

use App\Models\Actividad;
use App\Models\Inscripcion;

/**
 * Cálculo y persistencia de los servicios de una inscripción (grabación, comidas,
 * transporte, hospedaje) y de sus invitados. Compartido entre la pantalla de pago
 * (GridActividadesController::finalizarPago) y la edición admin
 * (EstadoInscripcionesController::update).
 */
class InscripcionServiciosService
{
    /**
     * Montos de servicios del titular según los ids seleccionados, resueltos en
     * la moneda pedida (modelo de total dividido): cada clave monto* acumula la
     * porción en esa moneda, y los servicios SIN precio en ella se cobran en la
     * moneda principal y van al balde 'montoPrincipal'. Con $monedaId null (o la
     * principal) el comportamiento es el histórico: todo sale de la columna
     * plana y montoPrincipal queda en 0.
     *
     * @return array{montoGrabacion: float|null, montoComidas: float|null, montoTransporte: float|null, montoHospedaje: float|null, montoPrincipal: float}
     */
    public function montosServicios(
        Actividad $actividad,
        bool $incluyeGrabacion,
        array $comidasIds,
        array $transportesIds,
        array $hospedajesIds,
        ?int $monedaId = null
    ): array {
        $actividad->loadMissing(['grabacion.precios', 'comidas.precios', 'transportes.precios', 'hospedajes.precios']);

        $montoPrincipal = 0.0;

        $sumar = function ($servicios, array $ids) use ($monedaId, &$montoPrincipal): float {
            $total = 0.0;
            foreach ($servicios->whereIn('id', $ids) as $servicio) {
                $precio = $servicio->precioEnMoneda($monedaId);
                if ($precio === null) {
                    // Sin precio en la moneda elegida: se cobra en la principal.
                    $montoPrincipal += (float) $servicio->precioEnMoneda(null);
                } else {
                    $total += $precio;
                }
            }
            return $total;
        };

        $montoGrabacion = null;
        if ($incluyeGrabacion && $actividad->grabacion_id && $actividad->grabacion) {
            $precio = $actividad->grabacion->precioEnMoneda($monedaId);
            if ($precio === null) {
                $montoPrincipal += (float) $actividad->grabacion->precioEnMoneda(null);
                $montoGrabacion = 0.0;
            } else {
                $montoGrabacion = $precio;
            }
        }

        return [
            'montoGrabacion' => $montoGrabacion,
            'montoComidas' => !empty($comidasIds) ? $sumar($actividad->comidas, $comidasIds) : null,
            'montoTransporte' => !empty($transportesIds) ? $sumar($actividad->transportes, $transportesIds) : null,
            'montoHospedaje' => !empty($hospedajesIds) ? $sumar($actividad->hospedajes, $hospedajesIds) : null,
            'montoPrincipal' => $montoPrincipal,
        ];
    }

    /**
     * ¿La actividad permite que un invitado curse online? Sólo si es "Presencial y Online Abierta".
     */
    public function modalidadPermiteInvitadoOnline(Actividad $actividad): bool
    {
        return $this->normalizarTexto($actividad->modalidad?->nombre) === 'presencial y online abierta';
    }

    /**
     * Construye el payload de cada invitado calculando sus montos.
     * Los invitados NUNCA tienen descuento: pagan siempre el precio general.
     * Sólo pueden cursar online si la actividad es "Presencial y Online Abierta".
     *
     * @param  array<int, array<string, mixed>>  $invitados
     * @return array<int, array<string, mixed>>
     */
    public function prepararInvitados(Actividad $actividad, float $precioGeneral, array $invitados, ?int $monedaId = null): array
    {
        if (empty($invitados)) {
            return [];
        }

        $modalidadAbierta = $this->modalidadPermiteInvitadoOnline($actividad);

        $preparados = [];
        foreach ($invitados as $invitado) {
            $comidasIds = array_values(array_unique(array_map('intval', $invitado['comidas_ids'] ?? [])));
            $transportesIds = array_values(array_unique(array_map('intval', $invitado['transportes_ids'] ?? [])));
            $hospedajesIds = array_values(array_unique(array_map('intval', $invitado['hospedajes_ids'] ?? [])));

            $incluyeGrabacion = (bool) ($invitado['incluye_grabacion'] ?? false);
            $online = $modalidadAbierta && (bool) ($invitado['online'] ?? false);

            $montos = $this->montosServicios($actividad, $incluyeGrabacion, $comidasIds, $transportesIds, $hospedajesIds, $monedaId);

            // Porción en la moneda de la inscripción; lo que no tiene precio en
            // ella queda aparte, en la moneda principal (total dividido).
            $montoApagar = $precioGeneral
                + (float) ($montos['montoGrabacion'] ?? 0)
                + (float) ($montos['montoComidas'] ?? 0)
                + (float) ($montos['montoTransporte'] ?? 0)
                + (float) ($montos['montoHospedaje'] ?? 0);

            $preparados[] = [
                'nombre' => trim((string) ($invitado['nombre'] ?? '')),
                'apellido' => trim((string) ($invitado['apellido'] ?? '')),
                'telefono' => $invitado['telefono'] ?? null,
                'online' => $online,
                'incluye_grabacion' => $incluyeGrabacion,
                'montoActividad' => $precioGeneral,
                'montoGrabacion' => $montos['montoGrabacion'],
                'montoComidas' => $montos['montoComidas'],
                'montoTransporte' => $montos['montoTransporte'],
                'montoHospedaje' => $montos['montoHospedaje'],
                'montoapagar' => $montoApagar,
                'moneda_id' => $monedaId,
                'monto_moneda_principal' => $montos['montoPrincipal'] > 0 ? $montos['montoPrincipal'] : null,
                'comidas_ids' => $comidasIds,
                'transportes_ids' => $transportesIds,
                'hospedajes_ids' => $hospedajesIds,
            ];
        }

        return $preparados;
    }

    /**
     * Reemplaza los invitados de la inscripción por los nuevos (borra y recrea).
     *
     * @param  array<int, array<string, mixed>>  $invitadosData
     */
    public function persistirInvitados(Inscripcion $inscripcion, array $invitadosData): void
    {
        $inscripcion->invitados()->delete();

        foreach ($invitadosData as $data) {
            $invitado = $inscripcion->invitados()->create([
                'nombre' => $data['nombre'],
                'apellido' => $data['apellido'],
                'telefono' => $data['telefono'],
                'online' => $data['online'],
                'asistencia' => 'Pendiente',
                'incluye_grabacion' => $data['incluye_grabacion'],
                'montoActividad' => $data['montoActividad'],
                'montoGrabacion' => $data['montoGrabacion'],
                'montoComidas' => $data['montoComidas'],
                'montoTransporte' => $data['montoTransporte'],
                'montoHospedaje' => $data['montoHospedaje'],
                'montoapagar' => $data['montoapagar'],
                'moneda_id' => $data['moneda_id'] ?? null,
                'monto_moneda_principal' => $data['monto_moneda_principal'] ?? null,
            ]);

            $invitado->comidas()->sync($data['comidas_ids']);
            $invitado->transportes()->sync($data['transportes_ids']);
            $invitado->hospedajes()->sync($data['hospedajes_ids']);
        }
    }

    private function normalizarTexto(?string $texto): string
    {
        $normalized = mb_strtolower(trim((string) $texto), 'UTF-8');

        return strtr($normalized, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
        ]);
    }
}

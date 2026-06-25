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
     * Montos de servicios del titular según los ids seleccionados.
     *
     * @return array{montoGrabacion: float|null, montoComidas: float|null, montoTransporte: float|null, montoHospedaje: float|null}
     */
    public function montosServicios(
        Actividad $actividad,
        bool $incluyeGrabacion,
        array $comidasIds,
        array $transportesIds,
        array $hospedajesIds
    ): array {
        return [
            'montoGrabacion' => $incluyeGrabacion && $actividad->grabacion_id
                ? (float) ($actividad->grabacion?->valor ?? 0)
                : null,
            'montoComidas' => !empty($comidasIds)
                ? (float) $actividad->comidas()->whereIn('comidas.id', $comidasIds)->sum('comidas.precio')
                : null,
            'montoTransporte' => !empty($transportesIds)
                ? (float) $actividad->transportes()->whereIn('transportes.id', $transportesIds)->sum('transportes.precio')
                : null,
            'montoHospedaje' => !empty($hospedajesIds)
                ? (float) $actividad->hospedajes()->whereIn('hospedajes.id', $hospedajesIds)->sum('hospedajes.precio')
                : null,
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
    public function prepararInvitados(Actividad $actividad, float $precioGeneral, array $invitados): array
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

            $montos = $this->montosServicios($actividad, $incluyeGrabacion, $comidasIds, $transportesIds, $hospedajesIds);

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

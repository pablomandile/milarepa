<?php

namespace App\Services;

use App\Models\Actividad;
use App\Models\Hospedaje;
use App\Models\Inscripcion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Cupo de acomodaciones de hospedaje POR ACTIVIDAD.
 *
 * La disponibilidad se calcula en vivo (sin contador mutable):
 *   disponibles = actividad_hospedaje.cantidad − reservas activas
 * donde "reservas" = inscripciones del titular con ese hospedaje_id + filas de
 * invitado_hospedaje de invitados de esa actividad. cantidad NULL = ilimitado.
 *
 * Por eso borrar/editar una inscripción libera automáticamente (la reserva es
 * implícita). Sólo se valida disponibilidad al crear/editar, con bloqueo de fila.
 */
class HospedajeCupoService
{
    /**
     * Disponibilidad por acomodación de la actividad.
     *
     * @return array<int, int|null>  [hospedaje_id => disponibles] ; null = ilimitado
     */
    public function disponibles(Actividad $actividad, ?int $excluirInscripcionId = null): array
    {
        $actividad->loadMissing('hospedajes');

        $ids = $actividad->hospedajes->pluck('id')->all();
        if (empty($ids)) {
            return [];
        }

        $reservadas = $this->reservadasPorHospedaje($actividad->id, $ids, $excluirInscripcionId);

        $resultado = [];
        foreach ($actividad->hospedajes as $hospedaje) {
            $cantidad = $hospedaje->pivot->cantidad ?? null;
            $resultado[$hospedaje->id] = $cantidad === null
                ? null
                : max(0, (int) $cantidad - ($reservadas[$hospedaje->id] ?? 0));
        }

        return $resultado;
    }

    /**
     * Unidades requeridas por acomodación en ESTA inscripción (titular + invitados).
     *
     * @param  array<int, int>  $hospedajesIdsTitular  ids elegidos por el titular (se reserva el 1º)
     * @param  array<int, array<string, mixed>>  $invitadosData  payload de prepararInvitados()
     * @return array<int, int>  [hospedaje_id => unidades]
     */
    public function requeridos(array $hospedajesIdsTitular, array $invitadosData): array
    {
        $req = [];

        // El titular sólo persiste la primera acomodación (inscripciones.hospedaje_id).
        $titularId = $hospedajesIdsTitular[0] ?? null;
        if ($titularId) {
            $req[(int) $titularId] = ($req[(int) $titularId] ?? 0) + 1;
        }

        foreach ($invitadosData as $invitado) {
            foreach ($invitado['hospedajes_ids'] ?? [] as $hid) {
                $req[(int) $hid] = ($req[(int) $hid] ?? 0) + 1;
            }
        }

        return $req;
    }

    /**
     * Valida que haya cupo para las unidades requeridas. Debe llamarse DENTRO de la
     * transacción: bloquea las filas del pivote para serializar reservas concurrentes.
     *
     * @param  array<int, int>  $requeridos  [hospedaje_id => unidades]
     * @throws ValidationException  (422) si alguna acomodación queda sin cupo
     */
    public function validar(Actividad $actividad, array $requeridos, ?int $excluirInscripcionId = null): void
    {
        if (empty($requeridos)) {
            return;
        }

        $ids = array_keys($requeridos);

        // Bloqueo pesimista de las filas del pivote para evitar sobreventa concurrente.
        $cantidades = DB::table('actividad_hospedaje')
            ->where('actividad_id', $actividad->id)
            ->whereIn('hospedaje_id', $ids)
            ->lockForUpdate()
            ->pluck('cantidad', 'hospedaje_id');

        $reservadas = $this->reservadasPorHospedaje($actividad->id, $ids, $excluirInscripcionId);
        $nombres = Hospedaje::whereIn('id', $ids)->pluck('nombre', 'id');

        foreach ($requeridos as $hospedajeId => $unidades) {
            $cantidad = $cantidades[$hospedajeId] ?? null;
            if ($cantidad === null) {
                continue; // ilimitado
            }
            $usadas = ($reservadas[$hospedajeId] ?? 0) + $unidades;
            if ($usadas > (int) $cantidad) {
                $nombre = $nombres[$hospedajeId] ?? "#{$hospedajeId}";
                throw ValidationException::withMessages([
                    'hospedajes_ids' => "La acomodación \"{$nombre}\" ya no tiene cupo disponible.",
                ]);
            }
        }
    }

    /**
     * Reservas activas (titular + invitados) por acomodación para una actividad.
     *
     * @param  array<int, int>  $hospedajeIds
     * @return array<int, int>  [hospedaje_id => cantidad reservada]
     */
    private function reservadasPorHospedaje(int $actividadId, array $hospedajeIds, ?int $excluirInscripcionId): array
    {
        $titular = Inscripcion::query()
            ->where('actividad_id', $actividadId)
            ->whereIn('hospedaje_id', $hospedajeIds)
            ->when($excluirInscripcionId, fn ($q) => $q->where('id', '!=', $excluirInscripcionId))
            ->groupBy('hospedaje_id')
            ->selectRaw('hospedaje_id, COUNT(*) as c')
            ->pluck('c', 'hospedaje_id');

        $invitados = DB::table('invitado_hospedaje as ih')
            ->join('invitados as inv', 'inv.id', '=', 'ih.invitado_id')
            ->join('inscripciones as ins', 'ins.id', '=', 'inv.inscripcion_id')
            ->where('ins.actividad_id', $actividadId)
            ->whereIn('ih.hospedaje_id', $hospedajeIds)
            ->when($excluirInscripcionId, fn ($q) => $q->where('ins.id', '!=', $excluirInscripcionId))
            ->groupBy('ih.hospedaje_id')
            ->selectRaw('ih.hospedaje_id as hid, COUNT(*) as c')
            ->pluck('c', 'hid');

        $total = [];
        foreach ($hospedajeIds as $id) {
            $total[$id] = (int) ($titular[$id] ?? 0) + (int) ($invitados[$id] ?? 0);
        }

        return $total;
    }
}

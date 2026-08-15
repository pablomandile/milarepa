<?php

namespace App\Services;

use App\Models\Actividad;
use App\Models\Inscripcion;
use App\Models\Moneda;
use Carbon\Carbon;

class ReporteInscripcionesPorActividadService
{
    public function construirReporte(): array
    {
        $ultimosCincoDias = now()->subDays(5)->startOfDay();
        $membresiaNormalizada = "LOWER(TRIM(REPLACE(REPLACE(COALESCE(membresia, ''), 'í', 'i'), 'Í', 'i')))";

        $inscripcionesBase = Inscripcion::query()
            ->whereHas('actividad', function ($query) {
                $query->where('estado', true);
            });

        $totalEventosActivos = Actividad::query()
            ->where('estado', true)
            ->count();

        $totalInscriptos = (clone $inscripcionesBase)->count();

        $inscriptosConTk = (clone $inscripcionesBase)
            ->whereRaw("{$membresiaNormalizada} <> ''")
            ->whereRaw("{$membresiaNormalizada} <> 'sin membresia'")
            ->count();

        $inscriptosSinTk = max(0, $totalInscriptos - $inscriptosConTk);

        $inscriptosUltimos5Dias = (clone $inscripcionesBase)
            ->where('created_at', '>=', $ultimosCincoDias)
            ->count();

        $pendientesPago = (clone $inscripcionesBase)
            ->whereIn('pago', ['Parcial', 'Pendiente'])
            ->count();

        $calcularPorcentaje = static function (int $parte, int $total): float {
            if ($total <= 0) {
                return 0;
            }

            return round(($parte / $total) * 100, 1);
        };

        $actividades = Actividad::query()
            ->where('estado', true)
            ->whereHas('inscripciones')
            ->with(['maestros:id,nombre'])
            ->withCount('inscripciones as total_inscriptos')
            ->withCount([
                'inscripciones as inscriptos_ultimos_5_dias' => function ($query) use ($ultimosCincoDias) {
                    $query->where('created_at', '>=', $ultimosCincoDias);
                },
            ])
            ->withCount([
                'inscripciones as pendientes_pago' => function ($query) {
                    $query->whereIn('pago', ['Parcial', 'Pendiente']);
                },
            ])
            ->orderBy('fecha_inicio', 'asc')
            ->get(['id', 'nombre', 'fecha_inicio']);

        $importesPendientes = $this->importesPendientesPorMoneda($actividades->pluck('id')->all());

        $actividades = $actividades
            ->map(function ($actividad) use ($importesPendientes) {
                $fechaInicio = $actividad->fecha_inicio ? Carbon::parse($actividad->fecha_inicio) : null;
                $porMoneda = $importesPendientes[$actividad->id] ?? [];

                return [
                    'id' => $actividad->id,
                    'nombre' => $actividad->nombre,
                    'maestro' => $actividad->maestros->pluck('nombre')->filter()->implode(', '),
                    'fecha' => $fechaInicio ? $fechaInicio->toDateString() : null,
                    'fecha_formateada' => $fechaInicio ? $fechaInicio->translatedFormat('j \\d\\e F') : '-',
                    'dias_restantes' => $fechaInicio ? now()->startOfDay()->diffInDays($fechaInicio->copy()->startOfDay(), false) : null,
                    'total_inscriptos' => (int) ($actividad->total_inscriptos ?? 0),
                    'inscriptos_ultimos_5_dias' => (int) ($actividad->inscriptos_ultimos_5_dias ?? 0),
                    'pendientes_pago' => (int) ($actividad->pendientes_pago ?? 0),
                    // Compatibilidad: el importe en la moneda principal, que es lo que
                    // mostraba esta clave antes (con la diferencia de que ya no se le
                    // suman de prepo los montos en otras monedas).
                    'pendiente_importe' => (float) ($porMoneda['principal'] ?? 0),
                    // Desglose completo, una entrada por moneda y la principal primero.
                    'pendiente_importes' => $porMoneda['por_moneda'] ?? [],
                ];
            })
            ->values()
            ->all();

        return [
            'actividades' => $actividades,
            'resumen' => [
                'eventos_activos' => $totalEventosActivos,
                'total_inscriptos' => $totalInscriptos,
                'inscriptos_con_tk' => $inscriptosConTk,
                'inscriptos_con_tk_pct' => $calcularPorcentaje($inscriptosConTk, $totalInscriptos),
                'inscriptos_sin_tk' => $inscriptosSinTk,
                'inscriptos_sin_tk_pct' => $calcularPorcentaje($inscriptosSinTk, $totalInscriptos),
                'inscriptos_ultimos_5_dias' => $inscriptosUltimos5Dias,
                'pendientes_pago' => $pendientesPago,
            ],
            'rango' => [
                'desde' => now()->startOfWeek()->translatedFormat('j \\d\\e F'),
                'hasta' => now()->endOfWeek()->translatedFormat('j \\d\\e F'),
            ],
        ];
    }

    /**
     * Importe pendiente de cada actividad, abierto por moneda.
     *
     * Antes era un `withSum('montoapagar')` que metía pesos y dólares en el mismo
     * número. Ahora cada inscripción aporta su `montoapagar` a la moneda que tenga
     * (null = principal, según BUSINESS_RULES §2.1bis) y su `monto_moneda_principal`
     * —la porción de servicios sin precio en esa moneda— siempre a la principal,
     * que antes directamente no se contaba.
     *
     * @param  array<int, int>  $actividadIds
     * @return array<int, array{principal: float, por_moneda: array<int, array{moneda_id: int|null, simbolo: string, importe: float}>}>
     */
    public function importesPendientesPorMoneda(array $actividadIds): array
    {
        if (empty($actividadIds)) {
            return [];
        }

        $principalId = Moneda::principalId() ?? 0;
        $simbolos = Moneda::pluck('simbolo', 'id');
        $simboloPrincipal = $simbolos[$principalId] ?? '$';

        $filas = Inscripcion::query()
            ->whereIn('actividad_id', $actividadIds)
            ->whereIn('pago', ['Parcial', 'Pendiente'])
            ->selectRaw('actividad_id, moneda_id, SUM(montoapagar) as total, SUM(COALESCE(monto_moneda_principal, 0)) as total_principal')
            ->groupBy('actividad_id', 'moneda_id')
            ->get();

        $acumulado = [];
        foreach ($filas as $fila) {
            $actividadId = (int) $fila->actividad_id;
            $monedaId = $fila->moneda_id ? (int) $fila->moneda_id : $principalId;

            $acumulado[$actividadId][$monedaId] = ($acumulado[$actividadId][$monedaId] ?? 0) + (float) $fila->total;

            $porcionPrincipal = (float) $fila->total_principal;
            if ($porcionPrincipal > 0) {
                $acumulado[$actividadId][$principalId] = ($acumulado[$actividadId][$principalId] ?? 0) + $porcionPrincipal;
            }
        }

        $resultado = [];
        foreach ($acumulado as $actividadId => $porMoneda) {
            $lista = [];

            if (($porMoneda[$principalId] ?? 0) > 0) {
                $lista[] = [
                    'moneda_id' => $principalId ?: null,
                    'simbolo' => $simboloPrincipal,
                    'importe' => round($porMoneda[$principalId], 2),
                ];
            }

            foreach ($porMoneda as $monedaId => $importe) {
                if ($monedaId === $principalId || $importe <= 0) {
                    continue;
                }
                $lista[] = [
                    'moneda_id' => $monedaId,
                    'simbolo' => $simbolos[$monedaId] ?? '$',
                    'importe' => round($importe, 2),
                ];
            }

            $resultado[$actividadId] = [
                'principal' => round($porMoneda[$principalId] ?? 0, 2),
                'por_moneda' => $lista,
            ];
        }

        return $resultado;
    }
}

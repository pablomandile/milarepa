<?php

namespace App\Services;

use App\Models\Actividad;
use App\Models\Inscripcion;
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
            ->withSum([
                'inscripciones as pendiente_importe' => function ($query) {
                    $query->whereIn('pago', ['Parcial', 'Pendiente']);
                },
            ], 'montoapagar')
            ->orderBy('fecha_inicio', 'asc')
            ->get(['id', 'nombre', 'fecha_inicio'])
            ->map(function ($actividad) {
                $fechaInicio = $actividad->fecha_inicio ? Carbon::parse($actividad->fecha_inicio) : null;

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
                    'pendiente_importe' => (float) ($actividad->pendiente_importe ?? 0),
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
}

<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\Clase;
use App\Models\OracionCantada;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CalendarioController extends Controller
{
    public function index(Request $request)
    {
        $monthParam = (string) $request->query('month', now()->format('Y-m'));
        $month = $this->resolveMonth($monthParam);

        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        $actividades = Actividad::query()
            ->where('estado', true)
            ->whereDate('fecha_inicio', '<=', $monthEnd->toDateString())
            ->where(function ($query) use ($monthStart) {
                $query->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>=', $monthStart->toDateString());
            })
            ->orderBy('fecha_inicio')
            ->get(['id', 'nombre', 'fecha_inicio', 'fecha_fin'])
            ->map(function ($actividad) {
                $inicio = Carbon::parse($actividad->fecha_inicio);
                $fin = $actividad->fecha_fin
                    ? Carbon::parse($actividad->fecha_fin)
                    : $inicio->copy();

                return [
                    'id' => $actividad->id,
                    'nombre' => $actividad->nombre,
                    'fecha_inicio' => $inicio->toDateString(),
                    'fecha_fin' => $fin->toDateString(),
                    'hora_inicio' => $inicio->format('H:i'),
                ];
            })
            ->values();

        $oracionesCantadas = $this->buildOracionesCantadasCalendarItems($monthStart, $monthEnd);
        $clases = $this->buildClasesCalendarItems($monthStart, $monthEnd);
        $clasesEntidades = $this->buildClasesEntidades($monthStart);

        return inertia('Calendario/Index', [
            'calendar' => [
                'month' => $month->format('Y-m'),
                'year' => (int) $month->year,
                'monthNumber' => (int) $month->month,
                'monthLabel' => ucfirst($month->locale('es')->translatedFormat('F Y')),
                'firstDayOfMonth' => $monthStart->toDateString(),
                'daysInMonth' => $monthStart->daysInMonth,
                'startWeekday' => (int) $monthStart->dayOfWeekIso, // 1=lunes, 7=domingo
                'prevMonth' => $month->copy()->subMonth()->format('Y-m'),
                'nextMonth' => $month->copy()->addMonth()->format('Y-m'),
                'today' => now()->toDateString(),
            ],
            'actividades' => $actividades,
            'oracionesCantadas' => $oracionesCantadas,
            'clases' => $clases,
            'clasesEntidades' => $clasesEntidades,
        ]);
    }

    private function buildClasesEntidades(Carbon $monthStart)
    {
        return Clase::query()
            ->where('mostrar_en_calendario', true)
            ->where('mes_referencia', $monthStart->format('Y-m'))
            ->whereNotNull('entidad_id')
            ->with(['entidad:id,nombre'])
            ->get(['entidad_id'])
            ->map(function ($clase) {
                return [
                    'id' => $clase->entidad_id,
                    'nombre' => $clase->entidad?->nombre ?? ('Entidad ' . $clase->entidad_id),
                ];
            })
            ->unique('id')
            ->sortBy('nombre')
            ->values();
    }

    private function buildClasesCalendarItems(Carbon $monthStart, Carbon $monthEnd)
    {
        $weekdayMap = [
            1 => 'lunes',
            2 => 'martes',
            3 => 'miercoles',
            4 => 'jueves',
            5 => 'viernes',
            6 => 'sabado',
            7 => 'domingo',
        ];

        $items = collect();

        $clases = Clase::query()
            ->where('mostrar_en_calendario', true)
            ->where('mes_referencia', $monthStart->format('Y-m'))
            ->with(['entidad:id,nombre'])
            ->orderBy('horario_desde')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'entidad_id', 'dias_semana', 'horario_desde', 'titulos_por_fecha']);

        foreach ($clases as $clase) {
            $diasSemana = collect($clase->dias_semana ?? [])->map(fn ($d) => (string) $d)->values();
            if ($diasSemana->isEmpty()) {
                continue;
            }

            for ($cursor = $monthStart->copy(); $cursor->lte($monthEnd); $cursor->addDay()) {
                $weekday = $weekdayMap[$cursor->dayOfWeekIso] ?? null;
                if (!$weekday || !$diasSemana->contains($weekday)) {
                    continue;
                }

                $fecha = $cursor->toDateString();
                $tituloPorFecha = data_get($clase->titulos_por_fecha, $fecha);

                $items->push([
                    'id' => $clase->id,
                    'nombre' => filled($tituloPorFecha) ? $tituloPorFecha : $clase->nombre,
                    'fecha' => $fecha,
                    'hora' => $clase->horario_desde ? Carbon::parse($clase->horario_desde)->format('H:i') : null,
                    'entidad_id' => $clase->entidad_id,
                    'entidad_nombre' => $clase->entidad?->nombre,
                    'tipo' => 'clase',
                ]);
            }
        }

        return $items
            ->sortBy([
                ['fecha', 'asc'],
                ['hora', 'asc'],
                ['nombre', 'asc'],
            ])
            ->values();
    }

    private function buildOracionesCantadasCalendarItems(Carbon $monthStart, Carbon $monthEnd)
    {
        $weekdayMap = [
            1 => 'lunes',
            2 => 'martes',
            3 => 'miercoles',
            4 => 'jueves',
            5 => 'viernes',
            6 => 'sabado',
            7 => 'domingo',
        ];

        $items = collect();

        $oraciones = OracionCantada::query()
            ->where('mostrar_en_calendario', true)
            ->orderBy('hora')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'periodicidad', 'dia', 'dias_semana', 'hora']);

        foreach ($oraciones as $oracion) {
            if ($oracion->periodicidad === 'Mensual') {
                $dia = (int) ($oracion->dia ?? 0);
                if ($dia === 29 && $monthStart->daysInMonth === 28) {
                    $dia = 28;
                }

                if ($dia < 1 || $dia > $monthStart->daysInMonth) {
                    continue;
                }

                $fecha = $monthStart->copy()->day($dia);
                $items->push([
                    'id' => $oracion->id,
                    'nombre' => $oracion->nombre,
                    'fecha' => $fecha->toDateString(),
                    'hora' => $oracion->hora ? Carbon::parse($oracion->hora)->format('H:i') : null,
                    'tipo' => 'oracion',
                ]);
                continue;
            }

            if ($oracion->periodicidad !== 'Diaria') {
                continue;
            }

            $diasSemana = collect($oracion->dias_semana ?? [])->map(fn ($d) => (string) $d)->values();
            if ($diasSemana->isEmpty()) {
                continue;
            }

            for ($cursor = $monthStart->copy(); $cursor->lte($monthEnd); $cursor->addDay()) {
                $weekday = $weekdayMap[$cursor->dayOfWeekIso] ?? null;
                if (!$weekday || !$diasSemana->contains($weekday)) {
                    continue;
                }

                $items->push([
                    'id' => $oracion->id,
                    'nombre' => $oracion->nombre,
                    'fecha' => $cursor->toDateString(),
                    'hora' => $oracion->hora ? Carbon::parse($oracion->hora)->format('H:i') : null,
                    'tipo' => 'oracion',
                ]);
            }
        }

        return $items
            ->sortBy([
                ['fecha', 'asc'],
                ['hora', 'asc'],
                ['nombre', 'asc'],
            ])
            ->values();
    }

    private function resolveMonth(string $monthParam): Carbon
    {
        if (preg_match('/^\d{4}-\d{2}$/', $monthParam) !== 1) {
            return now()->startOfMonth();
        }

        try {
            return Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth();
        } catch (\Throwable $e) {
            return now()->startOfMonth();
        }
    }
}

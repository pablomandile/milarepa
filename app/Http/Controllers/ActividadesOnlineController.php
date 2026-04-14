<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\Clase;
use App\Models\Ciclo;
use App\Models\OracionCantada;
use App\Models\PaginaActividadOnline;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ActividadesOnlineController extends Controller
{
    public function index()
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $monthKey = $monthStart->format('Y-m');

        $pagina = PaginaActividadOnline::with('imagen')
            ->where('mes_referencia', $monthKey)
            ->latest('id')
            ->first();

        return inertia('Paginas/ActividadesOnline', [
            'monthLabel' => ucfirst($monthStart->locale('es')->translatedFormat('F')),
            'cycleName' => $this->resolveCycleName($monthStart),
            'headerImageUrl' => $pagina?->imagen ? '/storage/' . $pagina->imagen->ruta : null,
            'oraciones' => $this->buildOracionesOnline($monthStart, $monthEnd)->values(),
            'clases' => $this->buildClasesOnline($monthStart, $monthEnd)->values(),
            'cursos' => $this->buildCursosOnline($monthStart)->values(),
        ]);
    }

    private function buildOracionesOnline(Carbon $monthStart, Carbon $monthEnd): Collection
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
            ->whereHas('modalidad', function ($query) {
                $query->whereRaw('LOWER(TRIM(nombre)) <> ?', ['presencial']);
            })
            ->with('stream.links')
            ->orderBy('hora')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'periodicidad', 'dia', 'dias_semana', 'hora', 'configuracion_por_mes', 'imagen', 'stream_id']);

        foreach ($oraciones as $oracion) {
            $configuracion = $oracion->configuracionParaMes($monthStart);
            $diasSemana = collect($configuracion['dias_semana'] ?? [])->map(fn ($d) => (string) $d)->values();

            if ($configuracion['periodicidad'] === 'Diaria' && $diasSemana->isNotEmpty()) {
                for ($cursor = $monthStart->copy(); $cursor->lte($monthEnd); $cursor->addDay()) {
                    $weekday = $weekdayMap[$cursor->dayOfWeekIso] ?? null;
                    if (!$weekday || !$diasSemana->contains($weekday)) {
                        continue;
                    }

                    $items->push([
                        'id' => $oracion->id,
                        'nombre' => $oracion->nombre,
                        'fecha' => $cursor->toDateString(),
                        'hora' => $configuracion['hora'] ? Carbon::parse($configuracion['hora'])->format('H:i') : null,
                        'image_url' => $this->resolveImagePath($oracion->imagen),
                        'links' => $this->matchStreamLinks($oracion->nombre, $cursor, collect($oracion->stream?->links ?? [])),
                    ]);
                }
                continue;
            }

            if ($configuracion['periodicidad'] !== 'Mensual') {
                continue;
            }

            $dia = (int) ($configuracion['dia'] ?? 0);
            if ($dia === 29 && $monthStart->daysInMonth === 28) {
                $dia = 28;
            }

            if ($dia >= 1 && $dia <= $monthStart->daysInMonth) {
                $fecha = $monthStart->copy()->day($dia);
                $items->push([
                    'id' => $oracion->id,
                    'nombre' => $oracion->nombre,
                    'fecha' => $fecha->toDateString(),
                    'hora' => $configuracion['hora'] ? Carbon::parse($configuracion['hora'])->format('H:i') : null,
                    'image_url' => $this->resolveImagePath($oracion->imagen),
                    'links' => $this->matchStreamLinks($oracion->nombre, $fecha, collect($oracion->stream?->links ?? [])),
                ]);
            }
        }

        return $items->sortBy([
            ['fecha', 'asc'],
            ['hora', 'asc'],
            ['nombre', 'asc'],
        ]);
    }

    private function buildClasesOnline(Carbon $monthStart, Carbon $monthEnd): Collection
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
            ->where('activa', true)
            ->where('mes_referencia', $monthStart->format('Y-m'))
            ->whereHas('modalidad', function ($query) {
                $query->whereRaw('LOWER(TRIM(nombre)) <> ?', ['presencial']);
            })
            ->with(['imagen', 'stream.links'])
            ->orderBy('horario_desde')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'dias_semana', 'horario_desde', 'titulos_por_fecha', 'imagen_id', 'stream_id']);

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
                $tituloFecha = data_get($clase->titulos_por_fecha, $fecha);

                $items->push([
                    'id' => $clase->id,
                    'nombre' => $clase->nombre,
                    'fecha' => $fecha,
                    'hora' => $clase->horario_desde ? Carbon::parse($clase->horario_desde)->format('H:i') : null,
                    'titulo_fecha' => filled($tituloFecha) ? (string) $tituloFecha : null,
                    'button_text' => filled($tituloFecha) ? (string) $tituloFecha : 'Abrir link',
                    'image_url' => $clase->imagen ? '/storage/' . $clase->imagen->ruta : null,
                    'links' => $this->matchStreamLinksByDateToken($cursor, collect($clase->stream?->links ?? [])),
                ]);
            }
        }

        return $items->sortBy([
            ['fecha', 'asc'],
            ['hora', 'asc'],
            ['nombre', 'asc'],
        ]);
    }

    private function buildCursosOnline(Carbon $monthStart): Collection
    {
        return Actividad::query()
            ->where('estado', true)
            ->whereHas('modalidad', function ($query) {
                $query->whereRaw('LOWER(TRIM(nombre)) <> ?', ['presencial']);
            })
            ->whereDate('fecha_inicio', '>=', $monthStart->toDateString())
            ->with(['imagen', 'maestros:id,nombre'])
            ->orderBy('fecha_inicio')
            ->get(['id', 'nombre', 'fecha_inicio', 'fecha_fin', 'imagen_id'])
            ->map(function ($actividad) {
                return [
                    'id' => $actividad->id,
                    'nombre' => $actividad->nombre,
                    'fecha_inicio' => optional($actividad->fecha_inicio)->toDateString(),
                    'fecha_fin' => optional($actividad->fecha_fin)->toDateString(),
                    'hora_inicio' => optional($actividad->fecha_inicio)->format('H:i'),
                    'image_url' => $actividad->imagen ? '/storage/' . $actividad->imagen->ruta : null,
                    'maestros' => $actividad->maestros
                        ? $actividad->maestros->pluck('nombre')->filter()->values()->all()
                        : [],
                ];
            });
    }

    private function resolveImagePath(?string $path): ?string
    {
        if (!$path) return null;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }
        return '/storage/' . ltrim($path, '/');
    }

    private function matchStreamLinks(string $oracionNombre, Carbon $fecha, Collection $links): array
    {
        if ($links->isEmpty()) {
            return [];
        }

        $normalizedOracion = $this->normalizeText($oracionNombre);
        $oracionWords = collect(preg_split('/\s+/', $normalizedOracion))
            ->filter(fn ($word) => mb_strlen($word) >= 4)
            ->values();

        $dateTokenCurrentFormat = $this->normalizeText($fecha->locale('es')->translatedFormat('l j'));
        $dateTokens = collect([$dateTokenCurrentFormat])->filter()->unique()->values();

        $prepared = $links
            ->filter(fn ($link) => filled($link->link))
            ->map(function ($link) {
                return [
                    'id' => $link->id,
                    'nombre' => $link->nombre,
                    'url' => $this->resolveExternalUrl($link->link),
                    'normalized_name' => $this->normalizeText((string) $link->nombre),
                ];
            })
            ->values();

        $strict = $prepared->filter(function ($link) use ($dateTokens, $normalizedOracion, $oracionWords) {
            $name = $link['normalized_name'];
            $dateMatch = $dateTokens->contains(fn ($token) => $this->hasExactTokenMatch($name, $token));
            if (!$dateMatch) return false;

            if ($normalizedOracion !== '' && str_contains($name, $normalizedOracion)) {
                return true;
            }

            return $oracionWords->contains(fn ($word) => str_contains($name, $word));
        });

        $result = $strict->isNotEmpty()
            ? $strict
            : $prepared->filter(function ($link) use ($dateTokens) {
                $name = $link['normalized_name'];
                return $dateTokens->contains(fn ($token) => $this->hasExactTokenMatch($name, $token));
            });

        return $result
            ->map(fn ($link) => [
                'id' => $link['id'],
                'nombre' => $link['nombre'],
                'url' => $link['url'],
            ])
            ->values()
            ->all();
    }

    private function normalizeText(string $value): string
    {
        $normalized = Str::lower(trim($value));
        return strtr($normalized, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ñ' => 'n',
        ]);
    }

    private function resolveExternalUrl(string $url): string
    {
        $trimmed = trim($url);
        if (str_starts_with($trimmed, 'http://') || str_starts_with($trimmed, 'https://')) {
            return $trimmed;
        }
        return 'https://' . ltrim($trimmed, '/');
    }

    private function hasExactTokenMatch(string $text, string $token): bool
    {
        if ($token === '') {
            return false;
        }

        $pattern = '/\b' . preg_quote($token, '/') . '\b/u';
        return preg_match($pattern, $text) === 1;
    }

    private function matchStreamLinksByDateToken(Carbon $fecha, Collection $links): array
    {
        if ($links->isEmpty()) {
            return [];
        }

        $dateToken = $this->normalizeText($fecha->locale('es')->translatedFormat('l j')); // Ej: martes 3

        return $links
            ->filter(fn ($link) => filled($link->link))
            ->map(function ($link) {
                return [
                    'id' => $link->id,
                    'nombre' => (string) $link->nombre,
                    'url' => $this->resolveExternalUrl((string) $link->link),
                    'normalized_name' => $this->normalizeText((string) $link->nombre),
                ];
            })
            ->filter(fn ($link) => $this->hasExactTokenMatch($link['normalized_name'], $dateToken))
            ->map(fn ($link) => [
                'id' => $link['id'],
                'nombre' => $link['nombre'],
                'url' => $link['url'],
            ])
            ->values()
            ->all();
    }

    private function resolveCycleName(Carbon $monthStart): ?string
    {
        $mesActual = (int) $monthStart->format('n');

        $ciclo = Ciclo::query()
            ->where('mes', $mesActual)
            ->orderBy('id')
            ->first(['id', 'nombre']);

        return $ciclo?->nombre;
    }
}

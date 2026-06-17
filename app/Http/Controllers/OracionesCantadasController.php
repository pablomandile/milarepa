<?php

namespace App\Http\Controllers;

use App\Http\Requests\OracionCantadaRequest;
use App\Models\Modalidad;
use App\Models\OracionCantada;
use App\Models\Stream;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OracionesCantadasController extends Controller
{
    public function index()
    {
        $oracionesCantadas = OracionCantada::with(['stream', 'modalidad'])
            ->orderBy('periodicidad')
            ->orderBy('dia')
            ->orderBy('nombre')
            ->get();

        return Inertia::render('OracionesCantadas/Index', [
            'oracionesCantadas' => $oracionesCantadas,
        ]);
    }

    public function create()
    {
        return Inertia::render('OracionesCantadas/Create', [
            'streams' => Stream::orderBy('nombre')->get(),
            'modalidades' => Modalidad::orderBy('nombre')->get(),
        ]);
    }

    public function store(OracionCantadaRequest $request)
    {
        OracionCantada::create($this->normalizePayload($request->validated()));

        return redirect()->route('oracionescantadas.index');
    }

    public function show(string $id)
    {
        //
    }

    public function showPublic(Request $request, OracionCantada $oracionCantada)
    {
        $oracionCantada->load(['stream', 'modalidad']);
        $month = $this->resolveMonth((string) $request->query('month', now()->format('Y-m')));
        $configuracion = $oracionCantada->configuracionParaMes($month);

        $oracionCantada->setAttribute('periodicidad', $configuracion['periodicidad']);
        $oracionCantada->setAttribute('dia', $configuracion['dia']);
        $oracionCantada->setAttribute('dias_semana', $configuracion['dias_semana']);
        $oracionCantada->setAttribute('hora', $configuracion['hora']);

        return Inertia::render('OracionesCantadas/ShowPublic', [
            'oracionCantada' => $oracionCantada,
            'returnUrl' => $request->query('return_url'),
        ]);
    }

    /**
     * Página pública de Oraciones Cantadas. Mismo formato que la página de Clases
     * (sin banner, no aplica): tarjetas por oración con su cronograma del mes
     * (fecha y horario). Se excluyen las oraciones online (las que se transmiten
     * por stream o cuya modalidad es "Online") y nunca se exponen los links de stream.
     */
    public function paginaPublica()
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();
        $monthLabel = ucfirst($monthStart->locale('es')->translatedFormat('F'));

        $oraciones = OracionCantada::query()
            ->with(['modalidad:id,nombre'])
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'imagen', 'descripcion', 'periodicidad', 'dia', 'dias_semana', 'hora', 'configuracion_por_mes', 'stream_id', 'modalidad_id'])
            // "Todas menos las que son online": se descartan las transmitidas por stream
            // o cuya modalidad es exclusivamente "Online".
            ->reject(fn (OracionCantada $oracion) => $this->esOracionOnline($oracion))
            ->values();

        $oracionesData = $oraciones->map(function (OracionCantada $oracion) use ($monthStart, $monthEnd) {
            $config = $oracion->configuracionParaMes($monthStart);

            return [
                'id' => $oracion->id,
                'nombre' => $oracion->nombre,
                'image_url' => $oracion->imagen ?: null,
                'periodicidad' => $config['periodicidad'],
                'dia_label' => $this->oracionDiaLabel($config),
                'hora_label' => $config['hora'] ? Carbon::parse($config['hora'])->format('H:i') . ' hs' : null,
                'fechas' => $this->sesionesDeOracion($oracion, $monthStart, $monthEnd),
            ];
        })->values();

        return Inertia::render('Paginas/OracionesCantadas', [
            'monthLabel' => $monthLabel,
            'oraciones' => $oracionesData,
        ]);
    }

    /**
     * Una oración es "online" si se transmite por stream o si su modalidad es
     * exclusivamente "Online" (las híbridas "Presencial y Online" sin stream se
     * consideran presenciales y se muestran, pero sin el link de stream).
     */
    private function esOracionOnline(OracionCantada $oracion): bool
    {
        if ($oracion->stream_id) {
            return true;
        }

        return mb_strtolower(trim((string) optional($oracion->modalidad)->nombre)) === 'online';
    }

    /**
     * Etiqueta de día/días para la tarjeta: "Día N de cada mes" (Mensual) o los
     * días de la semana (Diaria), con rango "De X a Y" cuando son consecutivos.
     */
    private function oracionDiaLabel(array $config): string
    {
        if (($config['periodicidad'] ?? null) === 'Mensual') {
            $dia = (int) ($config['dia'] ?? 0);
            return $dia >= 1 ? "Día {$dia} de cada mes" : '-';
        }

        if (($config['periodicidad'] ?? null) !== 'Diaria') {
            return '-';
        }

        $orden = ['lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado', 'domingo'];
        $labels = [
            'lunes' => 'Lunes', 'martes' => 'Martes', 'miercoles' => 'Miércoles', 'jueves' => 'Jueves',
            'viernes' => 'Viernes', 'sabado' => 'Sábado', 'domingo' => 'Domingo',
        ];

        $dias = collect($config['dias_semana'] ?? [])->map(fn ($d) => (string) $d)->all();
        $ordered = array_values(array_filter($orden, fn ($d) => in_array($d, $dias, true)));

        if (empty($ordered)) {
            return '-';
        }
        if (count($ordered) === 7) {
            return 'Todos los días';
        }

        $indices = array_map(fn ($d) => array_search($d, $orden, true), $ordered);
        $consecutivos = true;
        for ($i = 1; $i < count($indices); $i++) {
            if ($indices[$i] !== $indices[$i - 1] + 1) {
                $consecutivos = false;
                break;
            }
        }

        if ($consecutivos && count($ordered) >= 3) {
            return 'De ' . $labels[$ordered[0]] . ' a ' . $labels[$ordered[count($ordered) - 1]];
        }

        return implode(', ', array_map(fn ($d) => $labels[$d], $ordered));
    }

    /**
     * Genera las fechas de una oración dentro del mes (fecha + hora), con la misma
     * lógica que usa el calendario (Mensual por día del mes, Diaria por días de la
     * semana, respetando la configuración personalizada por mes).
     */
    private function sesionesDeOracion(OracionCantada $oracion, Carbon $monthStart, Carbon $monthEnd): array
    {
        $weekdayMap = [
            1 => 'lunes', 2 => 'martes', 3 => 'miercoles', 4 => 'jueves',
            5 => 'viernes', 6 => 'sabado', 7 => 'domingo',
        ];

        $config = $oracion->configuracionParaMes($monthStart);
        $hora = $config['hora'] ? Carbon::parse($config['hora'])->format('H:i') : null;
        $fechas = [];

        if ($config['periodicidad'] === 'Mensual') {
            $dia = (int) ($config['dia'] ?? 0);
            if ($dia === 29 && $monthStart->daysInMonth === 28) {
                $dia = 28;
            }

            if ($dia >= 1 && $dia <= $monthStart->daysInMonth) {
                $fechas[] = [
                    'fecha' => $monthStart->copy()->day($dia)->toDateString(),
                    'hora' => $hora,
                ];
            }

            return $fechas;
        }

        if ($config['periodicidad'] !== 'Diaria') {
            return $fechas;
        }

        $diasSemana = collect($config['dias_semana'] ?? [])->map(fn ($d) => (string) $d);
        if ($diasSemana->isEmpty()) {
            return $fechas;
        }

        for ($cursor = $monthStart->copy(); $cursor->lte($monthEnd); $cursor->addDay()) {
            $weekday = $weekdayMap[$cursor->dayOfWeekIso] ?? null;
            if (!$weekday || !$diasSemana->contains($weekday)) {
                continue;
            }

            $fechas[] = [
                'fecha' => $cursor->toDateString(),
                'hora' => $hora,
            ];
        }

        return $fechas;
    }

    public function edit(OracionCantada $oracionCantada)
    {
        $oracionCantada->load(['stream', 'modalidad']);

        return Inertia::render('OracionesCantadas/Edit', [
            'oracionCantada' => $oracionCantada,
            'streams' => Stream::orderBy('nombre')->get(),
            'modalidades' => Modalidad::orderBy('nombre')->get(),
        ]);
    }

    public function update(OracionCantadaRequest $request, OracionCantada $oracionCantada)
    {
        $oracionCantada->update($this->normalizePayload($request->validated()));

        return redirect()->route('oracionescantadas.index');
    }

    public function destroy(OracionCantada $oracionCantada)
    {
        try {
            $oracionCantada->delete();

            return redirect()->route('oracionescantadas.index')
                ->with('success', 'La oración cantada fue eliminada con éxito.');
        } catch (\Throwable $e) {
            return redirect()->route('oracionescantadas.index')
                ->with('error', 'Error al eliminar la oración cantada: ' . $e->getMessage());
        }
    }

    private function normalizePayload(array $data): array
    {
        $periodicidad = $data['periodicidad'] ?? null;

        if ($periodicidad === 'Diaria') {
            $data['dia'] = null;
            $data['dias_semana'] = array_values(array_unique($data['dias_semana'] ?? []));
        } else {
            $data['dias_semana'] = null;
        }

        $data['configuracion_por_mes'] = collect($data['configuracion_por_mes'] ?? [])
            ->map(function (array $configuracion) {
                $periodicidad = $configuracion['periodicidad'] ?? null;
                $configuracionNormalizada = [
                    'mes' => (int) ($configuracion['mes'] ?? 0),
                    'periodicidad' => $periodicidad,
                    'dia' => $configuracion['dia'] ?? null,
                    'dias_semana' => $configuracion['dias_semana'] ?? [],
                    'hora' => $configuracion['hora'] ?? null,
                ];

                if ($periodicidad === 'Diaria') {
                    $configuracionNormalizada['dia'] = null;
                    $configuracionNormalizada['dias_semana'] = array_values(array_unique($configuracionNormalizada['dias_semana'] ?? []));
                } else {
                    $configuracionNormalizada['dias_semana'] = null;
                }

                return $configuracionNormalizada;
            })
            ->filter(fn (array $configuracion) => $configuracion['mes'] >= 1 && $configuracion['mes'] <= 12)
            ->unique('mes')
            ->sortBy('mes')
            ->values()
            ->all();

        return $data;
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

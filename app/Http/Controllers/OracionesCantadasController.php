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
        $oracionCantada->setAttribute('horarios_por_dia', $configuracion['horarios_por_dia']);

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
            ->get(['id', 'nombre', 'imagen', 'descripcion', 'periodicidad', 'dia', 'dias_semana', 'hora', 'horarios_por_dia', 'configuracion_por_mes', 'excepciones_por_fecha', 'stream_id', 'modalidad_id'])
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
                'fechas' => $oracion->sesionesDelMes($monthStart, $monthEnd),
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
            $data['horarios_por_dia'] = $this->normalizeHorariosPorDia($data['horarios_por_dia'] ?? [], $data['dias_semana']);
        } else {
            $data['dias_semana'] = null;
            $data['horarios_por_dia'] = null;
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
                    'horarios_por_dia' => null,
                ];

                if ($periodicidad === 'Diaria') {
                    $configuracionNormalizada['dia'] = null;
                    $configuracionNormalizada['dias_semana'] = array_values(array_unique($configuracionNormalizada['dias_semana'] ?? []));
                    $configuracionNormalizada['horarios_por_dia'] = $this->normalizeHorariosPorDia($configuracion['horarios_por_dia'] ?? [], $configuracionNormalizada['dias_semana']);
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

        $data['excepciones_por_fecha'] = $this->normalizeExcepcionesPorFecha($data['excepciones_por_fecha'] ?? []);

        return $data;
    }

    /**
     * Normaliza las excepciones por fecha: conserva la fecha (Y-m-d), la hora en
     * formato HH:mm (o null) y el mensaje sin espacios sobrantes (o null). Descarta
     * las excepciones que no aportan nada (sin hora ni mensaje) y las duplicadas.
     */
    private function normalizeExcepcionesPorFecha(array $excepciones): ?array
    {
        $normalizadas = collect($excepciones)
            ->map(function ($excepcion) {
                if (!is_array($excepcion)) {
                    return null;
                }

                $fecha = (string) ($excepcion['fecha'] ?? '');
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) !== 1) {
                    return null;
                }

                $hora = $excepcion['hora'] ?? null;
                $hora = is_string($hora) && preg_match('/^\d{2}:\d{2}$/', $hora) === 1 ? $hora : null;

                $mensaje = $excepcion['mensaje'] ?? null;
                $mensaje = is_string($mensaje) && trim($mensaje) !== '' ? trim($mensaje) : null;

                if ($hora === null && $mensaje === null) {
                    return null;
                }

                return ['fecha' => $fecha, 'hora' => $hora, 'mensaje' => $mensaje];
            })
            ->filter()
            ->unique('fecha')
            ->sortBy('fecha')
            ->values()
            ->all();

        return empty($normalizadas) ? null : $normalizadas;
    }

    /**
     * Conserva solo los horarios de los días realmente seleccionados y con un
     * valor de hora válido (HH:mm). Devuelve null si no queda ninguno.
     */
    private function normalizeHorariosPorDia(array $horarios, array $diasSemana): ?array
    {
        $normalizados = collect($horarios)
            ->only($diasSemana)
            ->filter(fn ($hora) => is_string($hora) && preg_match('/^\d{2}:\d{2}$/', $hora) === 1)
            ->all();

        return empty($normalizados) ? null : $normalizados;
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

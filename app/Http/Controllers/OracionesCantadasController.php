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

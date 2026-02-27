<?php

namespace App\Http\Controllers;

use App\Http\Requests\OracionCantadaRequest;
use App\Models\OracionCantada;
use Inertia\Inertia;

class OracionesCantadasController extends Controller
{
    public function index()
    {
        $oracionesCantadas = OracionCantada::orderBy('periodicidad')
            ->orderBy('dia')
            ->orderBy('nombre')
            ->paginate(15);

        return Inertia::render('OracionesCantadas/Index', [
            'oracionesCantadas' => $oracionesCantadas,
        ]);
    }

    public function create()
    {
        return Inertia::render('OracionesCantadas/Create');
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

    public function showPublic(OracionCantada $oracionCantada)
    {
        return Inertia::render('OracionesCantadas/ShowPublic', [
            'oracionCantada' => $oracionCantada,
        ]);
    }

    public function edit(OracionCantada $oracionCantada)
    {
        return Inertia::render('OracionesCantadas/Edit', [
            'oracionCantada' => $oracionCantada,
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

        return $data;
    }
}

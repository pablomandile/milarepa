<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventarioOtroRequest;
use App\Models\Entidad;
use App\Models\InventarioEntidadOtro;
use App\Models\Otro;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InventarioOtrosController extends Controller
{
    public function index(): Response
    {
        $entidadPrincipalId = $this->obtenerEntidadPrincipalId();

        $totalesPorOtro = InventarioEntidadOtro::query()
            ->selectRaw('otro_id, SUM(cantidad) as cantidad_total')
            ->groupBy('otro_id')
            ->pluck('cantidad_total', 'otro_id');

        $inventarios = InventarioEntidadOtro::with('otro.imagen')
            ->where('entidad_id', $entidadPrincipalId)
            ->orderByDesc('id')
            ->get()
            ->map(function (InventarioEntidadOtro $inventario) use ($totalesPorOtro) {
                $inventario->cantidad = (int) ($totalesPorOtro[(int) $inventario->otro_id] ?? 0);

                return $inventario;
            });

        return Inertia::render('InventarioOtros/Index', [
            'inventarios' => $inventarios,
        ]);
    }

    public function create(): Response
    {
        $entidadPrincipalId = $this->obtenerEntidadPrincipalId();
        $otrosEnPrincipal = InventarioEntidadOtro::query()
            ->where('entidad_id', $entidadPrincipalId)
            ->pluck('otro_id');

        return Inertia::render('InventarioOtros/Create', [
            'otros' => Otro::query()
                ->whereNotIn('id', $otrosEnPrincipal)
                ->orderBy('titulo')
                ->get(['id', 'titulo']),
        ]);
    }

    public function store(InventarioOtroRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['entidad_id'] = $this->obtenerEntidadPrincipalId();

        InventarioEntidadOtro::create($payload);

        return redirect()->route('inventario-otros.index')->with('success', 'Inventario creado correctamente.');
    }

    public function edit(InventarioEntidadOtro $inventario_otro): Response
    {
        $entidadPrincipalId = $this->obtenerEntidadPrincipalId();
        $otrosEnPrincipal = InventarioEntidadOtro::query()
            ->where('entidad_id', $entidadPrincipalId)
            ->where('id', '!=', $inventario_otro->id)
            ->pluck('otro_id');

        return Inertia::render('InventarioOtros/Edit', [
            'inventario' => $inventario_otro,
            'otros' => Otro::query()
                ->whereNotIn('id', $otrosEnPrincipal)
                ->orderBy('titulo')
                ->get(['id', 'titulo']),
        ]);
    }

    public function update(InventarioOtroRequest $request, InventarioEntidadOtro $inventario_otro): RedirectResponse
    {
        $payload = $request->validated();
        $payload['entidad_id'] = $this->obtenerEntidadPrincipalId();

        $inventario_otro->update($payload);

        return redirect()->route('inventario-otros.index')->with('success', 'Inventario actualizado correctamente.');
    }

    public function destroy(InventarioEntidadOtro $inventario_otro): RedirectResponse
    {
        $inventario_otro->delete();

        return redirect()->route('inventario-otros.index')->with('success', 'Inventario eliminado correctamente.');
    }

    private function obtenerEntidadPrincipalId(): int
    {
        return (int) Entidad::query()->where('entidad_principal', true)->value('id');
    }
}

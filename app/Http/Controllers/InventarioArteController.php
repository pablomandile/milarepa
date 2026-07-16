<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventarioArteRequest;
use App\Models\Arte;
use App\Models\Entidad;
use App\Models\InventarioEntidadArte;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InventarioArteController extends Controller
{
    public function index(): Response
    {
        $entidadPrincipalId = $this->obtenerEntidadPrincipalId();

        $totalesPorArte = InventarioEntidadArte::query()
            ->selectRaw('arte_id, SUM(cantidad) as cantidad_total')
            ->groupBy('arte_id')
            ->pluck('cantidad_total', 'arte_id');

        $inventarios = InventarioEntidadArte::with('arte.imagen')
            ->where('entidad_id', $entidadPrincipalId)
            ->orderByDesc('id')
            ->get()
            ->map(function (InventarioEntidadArte $inventario) use ($totalesPorArte) {
                $inventario->cantidad = (int) ($totalesPorArte[(int) $inventario->arte_id] ?? 0);

                return $inventario;
            });

        return Inertia::render('InventarioArte/Index', [
            'inventarios' => $inventarios,
        ]);
    }

    public function create(): Response
    {
        $entidadPrincipalId = $this->obtenerEntidadPrincipalId();
        $arteEnPrincipal = InventarioEntidadArte::query()
            ->where('entidad_id', $entidadPrincipalId)
            ->pluck('arte_id');

        return Inertia::render('InventarioArte/Create', [
            'arte' => Arte::query()
                ->whereNotIn('id', $arteEnPrincipal)
                ->orderBy('titulo')
                ->get(['id', 'titulo']),
        ]);
    }

    public function store(InventarioArteRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['entidad_id'] = $this->obtenerEntidadPrincipalId();

        InventarioEntidadArte::create($payload);

        return redirect()->route('inventario-arte.index')->with('success', 'Inventario de arte creado correctamente.');
    }

    public function edit(InventarioEntidadArte $inventario_arte): Response
    {
        $entidadPrincipalId = $this->obtenerEntidadPrincipalId();
        $arteEnPrincipal = InventarioEntidadArte::query()
            ->where('entidad_id', $entidadPrincipalId)
            ->where('id', '!=', $inventario_arte->id)
            ->pluck('arte_id');

        return Inertia::render('InventarioArte/Edit', [
            'inventario' => $inventario_arte,
            'arte' => Arte::query()
                ->whereNotIn('id', $arteEnPrincipal)
                ->orderBy('titulo')
                ->get(['id', 'titulo']),
        ]);
    }

    public function update(InventarioArteRequest $request, InventarioEntidadArte $inventario_arte): RedirectResponse
    {
        $payload = $request->validated();
        $payload['entidad_id'] = $this->obtenerEntidadPrincipalId();

        $inventario_arte->update($payload);

        return redirect()->route('inventario-arte.index')->with('success', 'Inventario de arte actualizado correctamente.');
    }

    public function destroy(InventarioEntidadArte $inventario_arte): RedirectResponse
    {
        $inventario_arte->delete();

        return redirect()->route('inventario-arte.index')->with('success', 'Inventario de arte eliminado correctamente.');
    }

    private function obtenerEntidadPrincipalId(): int
    {
        return (int) Entidad::query()->where('entidad_principal', true)->value('id');
    }
}

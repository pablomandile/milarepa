<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventarioOracionRequest;
use App\Models\Entidad;
use App\Models\InventarioEntidadOracion;
use App\Models\Oracion;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InventarioOracionesController extends Controller
{
    public function index(): Response
    {
        $entidadPrincipalId = $this->obtenerEntidadPrincipalId();

        $totalesPorOracion = InventarioEntidadOracion::query()
            ->selectRaw('oracion_id, SUM(cantidad) as cantidad_total')
            ->groupBy('oracion_id')
            ->pluck('cantidad_total', 'oracion_id');

        $inventarios = InventarioEntidadOracion::with('oracion.imagen')
            ->where('entidad_id', $entidadPrincipalId)
            ->orderByDesc('id')
            ->get()
            ->map(function (InventarioEntidadOracion $inventario) use ($totalesPorOracion) {
                $inventario->cantidad = (int) ($totalesPorOracion[(int) $inventario->oracion_id] ?? 0);

                return $inventario;
            });

        return Inertia::render('InventarioOraciones/Index', [
            'inventarios' => $inventarios,
        ]);
    }

    public function create(): Response
    {
        $entidadPrincipalId = $this->obtenerEntidadPrincipalId();
        $oracionesEnPrincipal = InventarioEntidadOracion::query()
            ->where('entidad_id', $entidadPrincipalId)
            ->pluck('oracion_id');

        return Inertia::render('InventarioOraciones/Create', [
            'oraciones' => Oracion::query()
                ->whereNotIn('id', $oracionesEnPrincipal)
                ->orderBy('titulo')
                ->get(['id', 'titulo']),
        ]);
    }

    public function store(InventarioOracionRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['entidad_id'] = $this->obtenerEntidadPrincipalId();

        InventarioEntidadOracion::create($payload);

        return redirect()->route('inventario-oraciones.index')->with('success', 'Inventario de oración creado correctamente.');
    }

    public function edit(InventarioEntidadOracion $inventario_oracion): Response
    {
        $entidadPrincipalId = $this->obtenerEntidadPrincipalId();
        $oracionesEnPrincipal = InventarioEntidadOracion::query()
            ->where('entidad_id', $entidadPrincipalId)
            ->where('id', '!=', $inventario_oracion->id)
            ->pluck('oracion_id');

        return Inertia::render('InventarioOraciones/Edit', [
            'inventario' => $inventario_oracion,
            'oraciones' => Oracion::query()
                ->whereNotIn('id', $oracionesEnPrincipal)
                ->orderBy('titulo')
                ->get(['id', 'titulo']),
        ]);
    }

    public function update(InventarioOracionRequest $request, InventarioEntidadOracion $inventario_oracion): RedirectResponse
    {
        $payload = $request->validated();
        $payload['entidad_id'] = $this->obtenerEntidadPrincipalId();

        $inventario_oracion->update($payload);

        return redirect()->route('inventario-oraciones.index')->with('success', 'Inventario de oración actualizado correctamente.');
    }

    public function destroy(InventarioEntidadOracion $inventario_oracion): RedirectResponse
    {
        $inventario_oracion->delete();

        return redirect()->route('inventario-oraciones.index')->with('success', 'Inventario de oración eliminado correctamente.');
    }

    private function obtenerEntidadPrincipalId(): int
    {
        return (int) Entidad::query()->where('entidad_principal', true)->value('id');
    }
}

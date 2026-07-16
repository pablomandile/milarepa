<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventarioArticuloTiendaRequest;
use App\Models\ArticuloTienda;
use App\Models\Entidad;
use App\Models\InventarioEntidadArticuloTienda;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InventarioArticulosTiendaController extends Controller
{
    public function index(): Response
    {
        $entidadPrincipalId = $this->obtenerEntidadPrincipalId();

        $totalesPorArticulo = InventarioEntidadArticuloTienda::query()
            ->selectRaw('articulo_tienda_id, SUM(cantidad) as cantidad_total')
            ->groupBy('articulo_tienda_id')
            ->pluck('cantidad_total', 'articulo_tienda_id');

        $inventarios = InventarioEntidadArticuloTienda::with('articuloTienda.imagen', 'articuloTienda.categoria')
            ->where('entidad_id', $entidadPrincipalId)
            ->orderByDesc('id')
            ->get()
            ->map(function (InventarioEntidadArticuloTienda $inventario) use ($totalesPorArticulo) {
                $inventario->cantidad = (int) ($totalesPorArticulo[(int) $inventario->articulo_tienda_id] ?? 0);

                return $inventario;
            });

        return Inertia::render('InventarioArticulosTienda/Index', [
            'inventarios' => $inventarios,
        ]);
    }

    public function create(): Response
    {
        $entidadPrincipalId = $this->obtenerEntidadPrincipalId();
        $articulosEnPrincipal = InventarioEntidadArticuloTienda::query()
            ->where('entidad_id', $entidadPrincipalId)
            ->pluck('articulo_tienda_id');

        return Inertia::render('InventarioArticulosTienda/Create', [
            'articulos' => ArticuloTienda::query()
                ->whereNotIn('id', $articulosEnPrincipal)
                ->orderBy('titulo')
                ->get(['id', 'titulo']),
        ]);
    }

    public function store(InventarioArticuloTiendaRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['entidad_id'] = $this->obtenerEntidadPrincipalId();

        InventarioEntidadArticuloTienda::create($payload);

        return redirect()->route('inventario-articulos-tienda.index')->with('success', 'Inventario de artículo creado correctamente.');
    }

    public function edit(InventarioEntidadArticuloTienda $inventario_articulo_tienda): Response
    {
        $entidadPrincipalId = $this->obtenerEntidadPrincipalId();
        $articulosEnPrincipal = InventarioEntidadArticuloTienda::query()
            ->where('entidad_id', $entidadPrincipalId)
            ->where('id', '!=', $inventario_articulo_tienda->id)
            ->pluck('articulo_tienda_id');

        return Inertia::render('InventarioArticulosTienda/Edit', [
            'inventario' => $inventario_articulo_tienda,
            'articulos' => ArticuloTienda::query()
                ->whereNotIn('id', $articulosEnPrincipal)
                ->orderBy('titulo')
                ->get(['id', 'titulo']),
        ]);
    }

    public function update(InventarioArticuloTiendaRequest $request, InventarioEntidadArticuloTienda $inventario_articulo_tienda): RedirectResponse
    {
        $payload = $request->validated();
        $payload['entidad_id'] = $this->obtenerEntidadPrincipalId();

        $inventario_articulo_tienda->update($payload);

        return redirect()->route('inventario-articulos-tienda.index')->with('success', 'Inventario de artículo actualizado correctamente.');
    }

    public function destroy(InventarioEntidadArticuloTienda $inventario_articulo_tienda): RedirectResponse
    {
        $inventario_articulo_tienda->delete();

        return redirect()->route('inventario-articulos-tienda.index')->with('success', 'Inventario de artículo eliminado correctamente.');
    }

    private function obtenerEntidadPrincipalId(): int
    {
        return (int) Entidad::query()->where('entidad_principal', true)->value('id');
    }
}

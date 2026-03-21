<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventarioLibroRequest;
use App\Models\Entidad;
use App\Models\InventarioEntidadLibro;
use App\Models\Libro;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InventarioLibrosController extends Controller
{
    public function index(): Response
    {
        $entidadPrincipalId = $this->obtenerEntidadPrincipalId();

        $totalesPorLibro = InventarioEntidadLibro::query()
            ->selectRaw('libro_id, SUM(cantidad) as cantidad_total')
            ->groupBy('libro_id')
            ->pluck('cantidad_total', 'libro_id');

        $inventarios = InventarioEntidadLibro::with('libro.imagen')
            ->where('entidad_id', $entidadPrincipalId)
            ->orderByDesc('id')
            ->get()
            ->map(function (InventarioEntidadLibro $inventario) use ($totalesPorLibro) {
                $inventario->cantidad = (int) ($totalesPorLibro[(int) $inventario->libro_id] ?? 0);

                return $inventario;
            });

        return Inertia::render('InventarioLibros/Index', [
            'inventarios' => $inventarios,
        ]);
    }

    public function create(): Response
    {
        $entidadPrincipalId = $this->obtenerEntidadPrincipalId();
        $librosEnPrincipal = InventarioEntidadLibro::query()
            ->where('entidad_id', $entidadPrincipalId)
            ->pluck('libro_id');

        return Inertia::render('InventarioLibros/Create', [
            'libros' => Libro::query()
                ->whereNotIn('id', $librosEnPrincipal)
                ->orderBy('titulo')
                ->get(['id', 'titulo']),
        ]);
    }

    public function store(InventarioLibroRequest $request): RedirectResponse
    {
        $payload = $request->validated();
        $payload['entidad_id'] = $this->obtenerEntidadPrincipalId();

        InventarioEntidadLibro::create($payload);

        return redirect()->route('inventario-libros.index')->with('success', 'Inventario de libro creado correctamente.');
    }

    public function edit(InventarioEntidadLibro $inventario_libro): Response
    {
        $entidadPrincipalId = $this->obtenerEntidadPrincipalId();
        $librosEnPrincipal = InventarioEntidadLibro::query()
            ->where('entidad_id', $entidadPrincipalId)
            ->where('id', '!=', $inventario_libro->id)
            ->pluck('libro_id');

        return Inertia::render('InventarioLibros/Edit', [
            'inventario' => $inventario_libro,
            'libros' => Libro::query()
                ->whereNotIn('id', $librosEnPrincipal)
                ->orderBy('titulo')
                ->get(['id', 'titulo']),
        ]);
    }

    public function update(InventarioLibroRequest $request, InventarioEntidadLibro $inventario_libro): RedirectResponse
    {
        $payload = $request->validated();
        $payload['entidad_id'] = $this->obtenerEntidadPrincipalId();

        $inventario_libro->update($payload);

        return redirect()->route('inventario-libros.index')->with('success', 'Inventario de libro actualizado correctamente.');
    }

    public function destroy(InventarioEntidadLibro $inventario_libro): RedirectResponse
    {
        $inventario_libro->delete();

        return redirect()->route('inventario-libros.index')->with('success', 'Inventario de libro eliminado correctamente.');
    }

    private function obtenerEntidadPrincipalId(): int
    {
        return (int) Entidad::query()->where('entidad_principal', true)->value('id');
    }
}

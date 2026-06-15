<?php

namespace App\Http\Controllers;

use App\Concerns\ProcesaImagenAlGuardar;
use App\Http\Requests\PaginaActividadOnlineRequest;
use App\Models\PaginaActividadOnline;
use App\Services\OptimizadorImagenService;

class PaginasActividadesOnlineController extends Controller
{
    use ProcesaImagenAlGuardar;

    public function index()
    {
        $paginas = PaginaActividadOnline::with('imagen')
            ->orderByDesc('mes_referencia')
            ->orderByDesc('created_at')
            ->get();

        return inertia('PaginasActividadesOnline/Index', [
            'paginas' => $paginas,
        ]);
    }

    public function create()
    {
        return inertia('PaginasActividadesOnline/Create');
    }

    public function store(PaginaActividadOnlineRequest $request, OptimizadorImagenService $optimizador)
    {
        $validated = $request->validated();
        unset($validated['imagen']); // archivo: se procesa aparte, no es columna

        $this->guardarConImagen($request->file('imagen'), 'img/pages', $optimizador, function ($imagenId) use ($validated) {
            if ($imagenId) {
                $validated['imagen_id'] = $imagenId;
            }
            return PaginaActividadOnline::create($validated);
        });

        return redirect()->route('paginas-actividades-online.index');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(PaginaActividadOnline $paginas_actividades_online)
    {
        $paginas_actividades_online->load('imagen');

        return inertia('PaginasActividadesOnline/Edit', [
            'pagina' => $paginas_actividades_online,
        ]);
    }

    public function update(PaginaActividadOnlineRequest $request, PaginaActividadOnline $paginas_actividades_online, OptimizadorImagenService $optimizador)
    {
        $validated = $request->validated();
        unset($validated['imagen']); // archivo: se procesa aparte, no es columna

        $this->guardarConImagen($request->file('imagen'), 'img/pages', $optimizador, function ($imagenId) use ($paginas_actividades_online, $validated) {
            if ($imagenId) {
                $validated['imagen_id'] = $imagenId;
            }
            $paginas_actividades_online->update($validated);
            return $paginas_actividades_online;
        });

        return redirect()->route('paginas-actividades-online.index');
    }

    public function destroy(PaginaActividadOnline $paginas_actividades_online)
    {
        try {
            $paginas_actividades_online->delete();

            return redirect()->route('paginas-actividades-online.index')
                ->with('success', 'Registro eliminado con exito.');
        } catch (\Throwable $e) {
            return redirect()->route('paginas-actividades-online.index')
                ->with('error', 'Error al eliminar: ' . $e->getMessage());
        }
    }
}


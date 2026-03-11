<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaginaActividadOnlineRequest;
use App\Models\PaginaActividadOnline;

class PaginasActividadesOnlineController extends Controller
{
    public function index()
    {
        $paginas = PaginaActividadOnline::with('imagen')
            ->orderByDesc('mes_referencia')
            ->orderByDesc('created_at')
            ->paginate(15);

        return inertia('PaginasActividadesOnline/Index', [
            'paginas' => $paginas,
        ]);
    }

    public function create()
    {
        return inertia('PaginasActividadesOnline/Create');
    }

    public function store(PaginaActividadOnlineRequest $request)
    {
        PaginaActividadOnline::create($request->validated());

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

    public function update(PaginaActividadOnlineRequest $request, PaginaActividadOnline $paginas_actividades_online)
    {
        $paginas_actividades_online->update($request->validated());

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


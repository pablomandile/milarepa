<?php

namespace App\Http\Controllers;

use App\Concerns\ProcesaImagenAlGuardar;
use App\Http\Requests\OracionRequest;
use App\Models\Oracion;
use App\Services\OptimizadorImagenService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OracionesController extends Controller
{
    use ProcesaImagenAlGuardar;

    public function index(): Response
    {
        $oraciones = Oracion::with('imagen')->orderBy('titulo')->get();

        return Inertia::render('Oraciones/Index', [
            'oraciones' => $oraciones,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Oraciones/Create');
    }

    public function store(OracionRequest $request, OptimizadorImagenService $optimizador): RedirectResponse
    {
        $validated = $request->validated();
        unset($validated['imagen']); // archivo: se procesa aparte, no es columna

        $this->guardarConImagen($request->file('imagen'), 'img/oraciones', $optimizador, function ($imagenId) use ($validated) {
            if ($imagenId) {
                $validated['imagen_id'] = $imagenId;
            }
            return Oracion::create($validated);
        });

        return redirect()->route('oraciones.index')->with('success', 'Oración creada correctamente.');
    }

    public function edit(Oracion $oracion): Response
    {
        $oracion->load('imagen');

        return Inertia::render('Oraciones/Edit', [
            'oracion' => $oracion,
        ]);
    }

    public function update(OracionRequest $request, Oracion $oracion, OptimizadorImagenService $optimizador): RedirectResponse
    {
        $validated = $request->validated();
        unset($validated['imagen']); // archivo: se procesa aparte, no es columna

        $this->guardarConImagen($request->file('imagen'), 'img/oraciones', $optimizador, function ($imagenId) use ($oracion, $validated) {
            if ($imagenId) {
                $validated['imagen_id'] = $imagenId;
            }
            $oracion->update($validated);
            return $oracion;
        });

        return redirect()->route('oraciones.index')->with('success', 'Oración actualizada correctamente.');
    }

    public function destroy(Oracion $oracion): RedirectResponse
    {
        $oracion->delete();

        return redirect()->route('oraciones.index')->with('success', 'Oración eliminada correctamente.');
    }
}

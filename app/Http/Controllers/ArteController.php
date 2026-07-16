<?php

namespace App\Http\Controllers;

use App\Concerns\ProcesaImagenAlGuardar;
use App\Http\Requests\ArteRequest;
use App\Models\Arte;
use App\Services\OptimizadorImagenService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ArteController extends Controller
{
    use ProcesaImagenAlGuardar;

    public function index(): Response
    {
        $arte = Arte::with('imagen')->orderBy('titulo')->get();

        return Inertia::render('Arte/Index', [
            'arte' => $arte,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Arte/Create');
    }

    public function store(ArteRequest $request, OptimizadorImagenService $optimizador): RedirectResponse
    {
        $validated = $request->validated();
        unset($validated['imagen']); // archivo: se procesa aparte, no es columna

        $this->guardarConImagen($request->file('imagen'), 'img/arte', $optimizador, function ($imagenId) use ($validated) {
            if ($imagenId) {
                $validated['imagen_id'] = $imagenId;
            }
            return Arte::create($validated);
        });

        return redirect()->route('arte.index')->with('success', 'Artículo de arte creado correctamente.');
    }

    public function edit(Arte $arte): Response
    {
        $arte->load('imagen');

        return Inertia::render('Arte/Edit', [
            'arte' => $arte,
        ]);
    }

    public function update(ArteRequest $request, Arte $arte, OptimizadorImagenService $optimizador): RedirectResponse
    {
        $validated = $request->validated();
        unset($validated['imagen']); // archivo: se procesa aparte, no es columna

        $this->guardarConImagen($request->file('imagen'), 'img/arte', $optimizador, function ($imagenId) use ($arte, $validated) {
            if ($imagenId) {
                $validated['imagen_id'] = $imagenId;
            }
            $arte->update($validated);
            return $arte;
        });

        return redirect()->route('arte.index')->with('success', 'Artículo de arte actualizado correctamente.');
    }

    public function destroy(Arte $arte): RedirectResponse
    {
        $arte->delete();

        return redirect()->route('arte.index')->with('success', 'Artículo de arte eliminado correctamente.');
    }
}

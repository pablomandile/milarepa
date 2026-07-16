<?php

namespace App\Http\Controllers;

use App\Concerns\ProcesaImagenAlGuardar;
use App\Http\Requests\OtroRequest;
use App\Models\Otro;
use App\Services\OptimizadorImagenService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class OtrosController extends Controller
{
    use ProcesaImagenAlGuardar;

    public function index(): Response
    {
        $otros = Otro::with('imagen')->orderBy('titulo')->get();

        return Inertia::render('Otros/Index', [
            'otros' => $otros,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Otros/Create');
    }

    public function store(OtroRequest $request, OptimizadorImagenService $optimizador): RedirectResponse
    {
        $validated = $request->validated();
        unset($validated['imagen']); // archivo: se procesa aparte, no es columna

        $this->guardarConImagen($request->file('imagen'), 'img/otros', $optimizador, function ($imagenId) use ($validated) {
            if ($imagenId) {
                $validated['imagen_id'] = $imagenId;
            }
            return Otro::create($validated);
        });

        return redirect()->route('otros.index')->with('success', 'Artículo creado correctamente.');
    }

    public function edit(Otro $otro): Response
    {
        $otro->load('imagen');

        return Inertia::render('Otros/Edit', [
            'otro' => $otro,
        ]);
    }

    public function update(OtroRequest $request, Otro $otro, OptimizadorImagenService $optimizador): RedirectResponse
    {
        $validated = $request->validated();
        unset($validated['imagen']); // archivo: se procesa aparte, no es columna

        $this->guardarConImagen($request->file('imagen'), 'img/otros', $optimizador, function ($imagenId) use ($otro, $validated) {
            if ($imagenId) {
                $validated['imagen_id'] = $imagenId;
            }
            $otro->update($validated);
            return $otro;
        });

        return redirect()->route('otros.index')->with('success', 'Artículo actualizado correctamente.');
    }

    public function destroy(Otro $otro): RedirectResponse
    {
        $otro->delete();

        return redirect()->route('otros.index')->with('success', 'Artículo eliminado correctamente.');
    }
}

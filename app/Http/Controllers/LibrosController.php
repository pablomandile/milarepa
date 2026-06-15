<?php

namespace App\Http\Controllers;

use App\Concerns\ProcesaImagenAlGuardar;
use App\Http\Requests\LibroRequest;
use App\Models\Libro;
use App\Services\OptimizadorImagenService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LibrosController extends Controller
{
    use ProcesaImagenAlGuardar;

    public function index(): Response
    {
        $libros = Libro::with('imagen')->orderBy('titulo')->get();

        return Inertia::render('Libros/Index', [
            'libros' => $libros,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Libros/Create');
    }

    public function store(LibroRequest $request, OptimizadorImagenService $optimizador): RedirectResponse
    {
        $validated = $request->validated();
        unset($validated['imagen']); // archivo: se procesa aparte, no es columna

        $this->guardarConImagen($request->file('imagen'), 'img/libros', $optimizador, function ($imagenId) use ($validated) {
            if ($imagenId) {
                $validated['imagen_id'] = $imagenId;
            }
            return Libro::create($validated);
        });

        return redirect()->route('libros.index')->with('success', 'Libro creado correctamente.');
    }

    public function edit(Libro $libro): Response
    {
        $libro->load('imagen');

        return Inertia::render('Libros/Edit', [
            'libro' => $libro,
        ]);
    }

    public function update(LibroRequest $request, Libro $libro, OptimizadorImagenService $optimizador): RedirectResponse
    {
        $validated = $request->validated();
        unset($validated['imagen']); // archivo: se procesa aparte, no es columna

        $this->guardarConImagen($request->file('imagen'), 'img/libros', $optimizador, function ($imagenId) use ($libro, $validated) {
            if ($imagenId) {
                $validated['imagen_id'] = $imagenId;
            }
            $libro->update($validated);
            return $libro;
        });

        return redirect()->route('libros.index')->with('success', 'Libro actualizado correctamente.');
    }

    public function destroy(Libro $libro): RedirectResponse
    {
        $libro->delete();

        return redirect()->route('libros.index')->with('success', 'Libro eliminado correctamente.');
    }
}

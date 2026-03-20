<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventarioLibroRequest;
use App\Models\InventarioLibro;
use App\Models\Libro;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class InventarioLibrosController extends Controller
{
    public function index(): Response
    {
        $inventarios = InventarioLibro::with('libro')->orderByDesc('id')->get();

        return Inertia::render('InventarioLibros/Index', [
            'inventarios' => $inventarios,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('InventarioLibros/Create', [
            'libros' => Libro::orderBy('titulo')->get(['id', 'titulo']),
        ]);
    }

    public function store(InventarioLibroRequest $request): RedirectResponse
    {
        InventarioLibro::create($request->validated());

        return redirect()->route('inventario-libros.index')->with('success', 'Inventario de libro creado correctamente.');
    }

    public function edit(InventarioLibro $inventario_libro): Response
    {
        return Inertia::render('InventarioLibros/Edit', [
            'inventario' => $inventario_libro,
            'libros' => Libro::orderBy('titulo')->get(['id', 'titulo']),
        ]);
    }

    public function update(InventarioLibroRequest $request, InventarioLibro $inventario_libro): RedirectResponse
    {
        $inventario_libro->update($request->validated());

        return redirect()->route('inventario-libros.index')->with('success', 'Inventario de libro actualizado correctamente.');
    }

    public function destroy(InventarioLibro $inventario_libro): RedirectResponse
    {
        $inventario_libro->delete();

        return redirect()->route('inventario-libros.index')->with('success', 'Inventario de libro eliminado correctamente.');
    }
}

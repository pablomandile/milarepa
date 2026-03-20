<?php

namespace App\Http\Controllers;

use App\Http\Requests\LibroRequest;
use App\Models\Imagen;
use App\Models\Libro;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LibrosController extends Controller
{
    public function index(): Response
    {
        $libros = Libro::with('imagen')->orderBy('titulo')->get();

        return Inertia::render('Libros/Index', [
            'libros' => $libros,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Libros/Create', [
            'imagenes' => Imagen::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function store(LibroRequest $request): RedirectResponse
    {
        Libro::create($request->validated());

        return redirect()->route('libros.index')->with('success', 'Libro creado correctamente.');
    }

    public function edit(Libro $libro): Response
    {
        return Inertia::render('Libros/Edit', [
            'libro' => $libro,
            'imagenes' => Imagen::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    public function update(LibroRequest $request, Libro $libro): RedirectResponse
    {
        $libro->update($request->validated());

        return redirect()->route('libros.index')->with('success', 'Libro actualizado correctamente.');
    }

    public function destroy(Libro $libro): RedirectResponse
    {
        $libro->delete();

        return redirect()->route('libros.index')->with('success', 'Libro eliminado correctamente.');
    }
}

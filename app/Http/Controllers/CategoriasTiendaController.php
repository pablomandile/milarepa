<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoriaTiendaRequest;
use App\Models\CategoriaTienda;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CategoriasTiendaController extends Controller
{
    public function index(): Response
    {
        $categorias = CategoriaTienda::query()
            ->withCount('articulos')
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        return Inertia::render('CategoriasTienda/Index', [
            'categorias' => $categorias,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('CategoriasTienda/Create');
    }

    public function store(CategoriaTiendaRequest $request): RedirectResponse
    {
        CategoriaTienda::create($request->validated());

        return redirect()->route('categorias-tienda.index')->with('success', 'Categoría creada correctamente.');
    }

    public function edit(CategoriaTienda $categoria_tienda): Response
    {
        return Inertia::render('CategoriasTienda/Edit', [
            'categoria' => $categoria_tienda,
        ]);
    }

    public function update(CategoriaTiendaRequest $request, CategoriaTienda $categoria_tienda): RedirectResponse
    {
        $categoria_tienda->update($request->validated());

        return redirect()->route('categorias-tienda.index')->with('success', 'Categoría actualizada correctamente.');
    }

    public function destroy(CategoriaTienda $categoria_tienda): RedirectResponse
    {
        if ($categoria_tienda->articulos()->exists()) {
            return redirect()->route('categorias-tienda.index')
                ->with('error', 'No se puede eliminar la categoría porque tiene artículos asociados.');
        }

        $categoria_tienda->delete();

        return redirect()->route('categorias-tienda.index')->with('success', 'Categoría eliminada correctamente.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Concerns\ProcesaImagenAlGuardar;
use App\Http\Requests\ArticuloTiendaRequest;
use App\Models\ArticuloTienda;
use App\Models\CategoriaTienda;
use App\Services\OptimizadorImagenService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ArticulosTiendaController extends Controller
{
    use ProcesaImagenAlGuardar;

    public function index(): Response
    {
        $articulos = ArticuloTienda::with('imagen', 'categoria')->orderBy('titulo')->get();

        return Inertia::render('ArticulosTienda/Index', [
            'articulos' => $articulos,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('ArticulosTienda/Create', [
            'categorias' => $this->categorias(),
        ]);
    }

    public function store(ArticuloTiendaRequest $request, OptimizadorImagenService $optimizador): RedirectResponse
    {
        $validated = $request->validated();
        unset($validated['imagen']); // archivo: se procesa aparte, no es columna

        $this->guardarConImagen($request->file('imagen'), 'img/tienda', $optimizador, function ($imagenId) use ($validated) {
            if ($imagenId) {
                $validated['imagen_id'] = $imagenId;
            }
            return ArticuloTienda::create($validated);
        });

        return redirect()->route('articulos-tienda.index')->with('success', 'Artículo de tienda creado correctamente.');
    }

    public function edit(ArticuloTienda $articulo_tienda): Response
    {
        $articulo_tienda->load('imagen');

        return Inertia::render('ArticulosTienda/Edit', [
            'articulo' => $articulo_tienda,
            'categorias' => $this->categorias(),
        ]);
    }

    public function update(ArticuloTiendaRequest $request, ArticuloTienda $articulo_tienda, OptimizadorImagenService $optimizador): RedirectResponse
    {
        $validated = $request->validated();
        unset($validated['imagen']); // archivo: se procesa aparte, no es columna

        $this->guardarConImagen($request->file('imagen'), 'img/tienda', $optimizador, function ($imagenId) use ($articulo_tienda, $validated) {
            if ($imagenId) {
                $validated['imagen_id'] = $imagenId;
            }
            $articulo_tienda->update($validated);
            return $articulo_tienda;
        });

        return redirect()->route('articulos-tienda.index')->with('success', 'Artículo de tienda actualizado correctamente.');
    }

    public function destroy(ArticuloTienda $articulo_tienda): RedirectResponse
    {
        $articulo_tienda->delete();

        return redirect()->route('articulos-tienda.index')->with('success', 'Artículo de tienda eliminado correctamente.');
    }

    private function categorias()
    {
        return CategoriaTienda::query()->orderBy('nombre')->get(['id', 'nombre']);
    }
}

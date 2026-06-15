<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Concerns\ProcesaImagenAlGuardar;
use App\Models\Maestro;
use Inertia\Inertia;
use App\Http\Requests\MaestroRequest;
use App\Services\OptimizadorImagenService;

class MaestrosController extends Controller
{
    use ProcesaImagenAlGuardar;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $maestros = Maestro::with('imagen')->orderBy('nombre')->get();
        return inertia('Maestros/Index', ['maestros' => $maestros]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Maestros/Create');
    }

    /**
     * Store a newly created resource in storage.
     * @param App\Http\Requests\MaestroRequest
     * @param \Illuminate\Http\Response
     */
    public function store(MaestroRequest $request, OptimizadorImagenService $optimizador)
    {
        $validated = $request->validated();
        unset($validated['imagen']); // archivo: se procesa aparte, no es columna

        $this->guardarConImagen($request->file('imagen'), 'img/maestros', $optimizador, function ($imagenId) use ($validated) {
            if ($imagenId) {
                $validated['imagen_id'] = $imagenId;
            }
            return Maestro::create($validated);
        });

        return redirect()->route('maestros.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $maestro = Maestro::with('imagen')->findOrFail($id);

        // Devolver la vista de edición
        return Inertia::render('Maestros/Edit', [
            'maestro' => $maestro,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MaestroRequest $request, $id, OptimizadorImagenService $optimizador)
    {
        $maestro = Maestro::findOrFail($id);
        $validated = $request->validated();
        unset($validated['imagen']); // archivo: se procesa aparte, no es columna

        $this->guardarConImagen($request->file('imagen'), 'img/maestros', $optimizador, function ($imagenId) use ($maestro, $validated) {
            if ($imagenId) {
                $validated['imagen_id'] = $imagenId;
            }
            $maestro->update($validated);
            return $maestro;
        });

        return redirect()->route('maestros.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $maestro = Maestro::findorfail($id);
            $maestro->delete();
            return redirect()->route('maestros.index')->with('success', 'Maestro eliminado con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('maestros.index')->with('error', 'Error al eliminar el Maestro: ' . $e->getMessage());
        }
    }
}

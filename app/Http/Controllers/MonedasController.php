<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Moneda;
use App\Http\Requests\MonedaRequest;
use Inertia\Inertia;

class MonedasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $monedas = Moneda::paginate(15);
        return inertia('Monedas/Index', ['monedas' => $monedas]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Monedas/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MonedaRequest $request)
    {
        Moneda::create($request->validated());
        return redirect()->route('monedas.index');
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
    public function edit(Moneda $moneda)
    {
        return Inertia::render('Monedas/Edit', [
            'moneda' => $moneda,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MonedaRequest $request, $id)
    {
        $moneda = Moneda::findOrFail($id);

        $moneda->update($request->validated());

        return redirect()->route('monedas.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $moneda = Moneda::findorfail($id);
            $moneda->delete();
            return redirect()->route('monedas.index')->with('success', 'Moneda eliminada con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('monedas.index')->with('error', 'Error al eliminar la Moneda: ' . $e->getMessage());
        }
    }
}

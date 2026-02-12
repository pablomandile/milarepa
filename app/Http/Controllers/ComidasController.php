<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Comida;
use App\Http\Requests\ComidaRequest;
use Inertia\Inertia;
use App\Models\BotonPago;

class ComidasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $comidas = Comida::with('botonPago')->paginate(15);
        return inertia('Comidas/Index', ['comidas' => $comidas]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $botonesPago = BotonPago::select('id', 'nombre')->get();
        return inertia('Comidas/Create', [
            'botonesPago' => $botonesPago,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ComidaRequest $request)
    {
        Comida::create($request->validated());
        return redirect()-> route('comidas.index');
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
    public function edit(Comida $comida)
    {
        $botonesPago = BotonPago::select('id', 'nombre')->get();
        return inertia::render('Comidas/Edit', [
            'comida' => $comida,
            'botonesPago' => $botonesPago,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ComidaRequest $request, Comida $comida)
    {
        $comida->update($request->validated());
        return redirect()->route('comidas.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Comida $comida)
    {
        try {
            $comida->delete();
            return redirect()->route('comidas.index')->with('success', 'Comida eliminada con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('comidas.index')->with('error', 'Error al eliminar la Comida: ' . $e->getMessage());
        }
    }
}

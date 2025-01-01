<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transporte;
use App\Http\Requests\TransporteRequest;
use Inertia\Inertia;

class TransportesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $transportes = Transporte::paginate(15);
        return inertia('Transportes/Index', ['transportes'=>$transportes]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Transportes/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TransporteRequest $request)
    {
        Transporte::create($request->validated());
        return redirect()->route('transportes.index');
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
    public function edit(Transporte $transporte)
    {
        return inertia::render('Transportes/Edit', ['transporte'=>$transporte]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TransporteRequest $request, Transporte $transporte)
    {
        $transporte->update($request->validated());
        return redirect()->route('transportes.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transporte $transporte)
    {
        try {
            $transporte->delete();
            return redirect()->route('transportes.index')->with('sucsess', 'El transportes se ha eliminado con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('transportes.index')->with('error', 'Error al eliminar el transportes: '. $e->getMessage());
        }
    }
}

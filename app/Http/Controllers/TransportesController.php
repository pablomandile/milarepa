<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transporte;
use App\Models\Moneda;
use App\Http\Requests\TransporteRequest;
use App\Services\ServicioPrecioService;
use Inertia\Inertia;
use App\Models\BotonPago;

class TransportesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $transportes = Transporte::with('botonPago')->get();
        return inertia('Transportes/Index', ['transportes'=>$transportes]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $botonesPago = BotonPago::select('id', 'nombre')->get();
        return inertia('Transportes/Create', [
            'botonesPago' => $botonesPago,
            'monedas' => Moneda::orderByDesc('es_principal')->get(['id', 'nombre', 'simbolo', 'es_principal']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TransporteRequest $request, ServicioPrecioService $servicioPrecios)
    {
        $transporte = Transporte::create($request->safe()->except('precios'));
        $servicioPrecios->sincronizar($transporte, $request->validated('precios') ?? []);
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
        $botonesPago = BotonPago::select('id', 'nombre')->get();
        return inertia::render('Transportes/Edit', [
            'transporte' => $transporte->load('precios'),
            'botonesPago' => $botonesPago,
            'monedas' => Moneda::orderByDesc('es_principal')->get(['id', 'nombre', 'simbolo', 'es_principal']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TransporteRequest $request, Transporte $transporte, ServicioPrecioService $servicioPrecios)
    {
        $transporte->update($request->safe()->except('precios'));
        $servicioPrecios->sincronizar($transporte, $request->validated('precios') ?? []);
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

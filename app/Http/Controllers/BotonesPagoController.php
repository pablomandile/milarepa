<?php

namespace App\Http\Controllers;

use App\Models\BotonPago;
use App\Models\MetodoPago;
use App\Http\Requests\BotonPagoRequest;
use Inertia\Inertia;

class BotonesPagoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $botones = BotonPago::with('metodoPago')->paginate(15);
        return inertia('BotonesPago/Index', ['botones' => $botones]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $metodosPago = MetodoPago::all();
        return inertia('BotonesPago/Create', ['metodosPago' => $metodosPago]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BotonPagoRequest $request)
    {
        BotonPago::create($request->validated());
        return redirect()->route('botonespago.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $boton = BotonPago::findOrFail($id);
        $metodosPago = MetodoPago::all();
        return Inertia::render('BotonesPago/Edit', [
            'boton' => $boton,
            'metodosPago' => $metodosPago
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BotonPagoRequest $request, BotonPago $botonpago)
    {
        $botonpago->update($request->validated());
        return redirect()->route('botonespago.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BotonPago $botonpago)
    {
        try {
            $botonpago->delete();
            return redirect()->route('botonespago.index')->with('success', 'Botón de pago eliminado con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('botonespago.index')->with('error', 'Error al eliminar el botón de pago: ' . $e->getMessage());
        }
    }
}

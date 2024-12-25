<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MetodoPago;
use App\Http\Requests\MetodoPagoRequest;
use Inertia\Inertia;



class MetodosPagoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $metodosPago = MetodoPago::paginate(15);
        return inertia('MetodosPago/Index', ['metodosPago' => $metodosPago]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('MetodosPago/Create');

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MetodoPagoRequest $request)
    {
        MetodoPago::create($request->validated());
        return redirect()->route('metodospago.index');
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
    public function edit(MetodoPago $metodopago)
    {
        return Inertia::render('MetodosPago/Edit', [
            'metodoPago' => $metodopago,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MetodoPagoRequest $request, MetodoPago $metodopago)
    {
        $metodopago->update($request->validated());
        return redirect()->route('metodospago.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MetodoPago $metodopago)
    {
        try {
            $metodopago->delete();
            return redirect()->route('metodospago.index')->with('success', 'Método de pago eliminado con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('metodospago.index')->with('error', 'Error al eliminar el Método de pago: ' . $e->getMessage());
        }
    }
}

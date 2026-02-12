<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Hospedaje;
use App\Models\LugarHospedaje;
use App\Http\Requests\HospedajeRequest;
use Inertia\Inertia;
use App\Models\BotonPago;

class HospedajesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $hospedajes = Hospedaje::with(['lugarHospedaje', 'botonPago'])->paginate(10);

        return inertia('Hospedajes/Index', ['hospedajes'=>$hospedajes]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $lugaresHospedaje = LugarHospedaje::select('id','nombre')->get();
        $botonesPago = BotonPago::select('id', 'nombre')->get();

        return inertia('Hospedajes/Create', [
            'lugaresHospedaje' => $lugaresHospedaje,
            'botonesPago' => $botonesPago,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(HospedajeRequest $request)
    {
        Hospedaje::create($request->validated());
        return redirect()->route('hospedajes.index');
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
    public function edit(Hospedaje $hospedaje)
    {
        $lugaresHospedaje = LugarHospedaje::select('id','nombre')->get();
        $botonesPago = BotonPago::select('id', 'nombre')->get();

        return inertia::render('Hospedajes/Edit', [
            'hospedaje' => $hospedaje,
            'lugaresHospedaje' => $lugaresHospedaje,
            'botonesPago' => $botonesPago,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(HospedajeRequest $request, Hospedaje $hospedaje)
    {
        $hospedaje->update($request->validated());
        return redirect()->route('hospedajes.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Hospedaje $hospedaje)
    {
        try {
            $hospedaje->delete();
            return redirect()->route('hospedajes.index')->with('sucsess', 'El hospedaje se ha eliminado con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('hospedajes.index')->with('error', 'Error al eliminar el hospedaje: '. $e->getMessage());
        }
    }
}

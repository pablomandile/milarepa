<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\LugarHospedaje;
use App\Http\Requests\LugarHospedajeRequest;
use Inertia\Inertia;

class LugaresHospedajeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $lugareshospedaje = LugarHospedaje::paginate(15);
        return inertia('LugaresHospedaje/Index', ['lugareshospedaje'=>$lugareshospedaje]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('LugaresHospedaje/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(LugarHospedajeRequest $request)
    {
        LugarHospedaje::create($request->validated());
        return redirect()->route('lugareshospedaje.index');
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
    public function edit(LugarHospedaje $lugarhospedaje)
    {
        return inertia::render('LugaresHospedaje/Edit', ['lugarhospedaje'=>$lugarhospedaje]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(LugarHospedajeRequest $request, LugarHospedaje $lugarhospedaje)
    {
        $lugarhospedaje->update($request->validated());
        return redirect()->route('lugareshospedaje.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LugarHospedaje $lugarhospedaje)
    {
        try {
            $lugarhospedaje->delete();
            return redirect()->route('lugareshospedaje.index')->with('sucsess', 'El hospedaje se ha eliminado con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('lugareshospedaje.index')->with('error', 'Error al eliminar el hospedaje: '. $e->getMessage());
        }
    }
}

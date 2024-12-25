<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TipoActividad;
use App\Http\Requests\TipoActividadRequest;
use Inertia\Inertia;

class TiposActividadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tiposActividad = TipoActividad::paginate(15);
        return inertia('TiposActividad/Index', ['tiposActividad'=> $tiposActividad]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('TiposActividad/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TipoActividadRequest $request)
    {
        TipoActividad::create($request->validated());
        return redirect()->route('tiposactividad.index');
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
        $tipoActividad = TipoActividad::findorfail($id);
        return inertia::render('TiposActividad/Edit', ['tipoActividad' => $tipoActividad]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TipoActividadRequest $request, TipoActividad $tipoactividad)
    {
        // dd($tipoactividad->toArray(), $request->validated());
        $tipoactividad->update($request->validated());
        return redirect()->route('tiposactividad.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TipoActividad $tipoactividad)
    {
        try {
            $tipoactividad->delete();
            return redirect()->route('tiposactividad.index')->with('success', 'Tipo de actividad eliminado con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('tiposactividad.index')->with('error', 'Error al eliminar el tipo de actividad: ' . $e->getMessage());
        }
    }
}

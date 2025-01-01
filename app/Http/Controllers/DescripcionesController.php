<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Descripcion;
use App\Http\Requests\DescripcionRequest;
use Inertia\Inertia;

class DescripcionesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $descripciones = Descripcion::paginate(15);
        return inertia('Descripciones/Index', ['descripciones'=>$descripciones]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Descripciones/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DescripcionRequest $request)
    {
        Descripcion::create($request->validated());
        return redirect()->route('descripciones.index');
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
    public function edit(Descripcion $descripcion)
    {
        return inertia::render('Descripciones/Edit', ['descripcion'=>$descripcion]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(DescripcionRequest $request, Descripcion $descripcion)
    {
        $descripcion->update($request->validated());
        return redirect()->route('descripciones.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Descripcion $descripcion)
    {
        try {
            $descripcion->delete();
            return redirect()->route('descripciones.index')->with('sucsess', 'La descripción se ha eliminado con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('descripciones.index')->with('error', 'Error al eliminar la descripción: '. $e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Programa;
use App\Http\Requests\ProgramaRequest;
use Inertia\Inertia;

class ProgramasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $programas = Programa::paginate(15);
        return inertia('Programas/Index', ['programas'=>$programas]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Programas/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProgramaRequest $request)
    {
        Programa::create($request->validated());
        return redirect()->route('programas.index');
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
    public function edit(Programa $programa)
    {
        return inertia::render('Programas/Edit', ['programa'=>$programa]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ProgramaRequest $request, Programa $programa)
    {
        $programa->update($request->validated());
        return redirect()->route('programas.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Programa $programa)
    {
        try {
            $programa->delete();
            return redirect()->route('programas.index')->with('sucsess', 'El programa se ha eliminado con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('programas.index')->with('error', 'Error al eliminar el programa: '. $e->getMessage());
        }
    }
}

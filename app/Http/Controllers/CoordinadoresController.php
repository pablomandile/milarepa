<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Coordinador;
use App\Http\Requests\CoordinadorRequest;
use Inertia\Inertia;


class CoordinadoresController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $coordinadores = Coordinador::paginate(15);
        return inertia('Coordinadores/Index', ['coordinadores' => $coordinadores]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Coordinadores/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CoordinadorRequest $request)
    {
        Coordinador::create($request->validated());
        return redirect()->route('coordinadores.index');
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
        $coordinador = Coordinador::findOrFail($id);

        // Devolver la vista de edición
        return Inertia::render('Coordinadores/Edit', [
            'coordinador' => $coordinador,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CoordinadorRequest $request, $id)
    {
        $coordinador = Coordinador::findOrFail($id);

        $coordinador->update($request->validated());

        return redirect()->route('coordinadores.index');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $coordinador = Coordinador::findorfail($id);
            $coordinador->delete();
            return redirect()->route('coordinadores.index')->with('success', 'Coordinador eliminado con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('coordinadores.index')->with('error', 'Error al eliminar el Coordinador: ' . $e->getMessage());
        }
    }
}

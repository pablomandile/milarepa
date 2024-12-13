<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Maestro;
use Inertia\Inertia;
use App\Http\Requests\MaestroRequest;


class MaestrosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $maestros = Maestro::paginate(15);
        return inertia('Maestros/Index', ['maestros' => $maestros]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Maestros/Create');
    }

    /**
     * Store a newly created resource in storage.
     * @param App\Http\Requests\MaestroRequest
     * @param \Illuminate\Http\Response
     */
    public function store(MaestroRequest $request)
    {
        Maestro::create($request->validated());
        return redirect()->route('maestros.index');
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
        $maestro = Maestro::findOrFail($id);

        // Devolver la vista de edición
        return Inertia::render('Maestros/Edit', [
            'maestro' => $maestro,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MaestroRequest $request, $id)
    {
        $maestro = Maestro::findOrFail($id);

        $maestro->update($request->validated());

        return redirect()->route('maestros.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $maestro = Maestro::findorfail($id);
            $maestro->delete();
            return redirect()->route('maestros.index')->with('success', 'Maestro eliminado con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('maestros.index')->with('error', 'Error al eliminar la disponibilidad: ' . $e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Novedad;
use App\Http\Requests\NovedadRequest;
use Inertia\Inertia;

class NovedadesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $novedades = Novedad::paginate(15);
        return inertia('Novedades/Index', ['novedades' => $novedades]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Novedades/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(NovedadRequest $request)
    {
        Novedad::create($request->validated());
        return redirect()->route('novedades.index');
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
    public function edit(Novedad $novedad)
    {
        return Inertia::render('Novedades/Edit', [
            'novedad' => $novedad,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(NovedadRequest $request, $id)
    {
        $novedad = Novedad::findOrFail($id);

        $novedad->update($request->validated());

        return redirect()->route('novedades.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $novedad = Novedad::findorfail($id);
            $novedad->delete();
            return redirect()->route('novedades.index')->with('success', 'Novedad eliminada con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('novedades.index')->with('error', 'Error al eliminar la Novedad: ' . $e->getMessage());
        }
    }
}

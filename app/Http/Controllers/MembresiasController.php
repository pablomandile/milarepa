<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Membresia;
use App\Http\Requests\MembresiaRequest;
use Inertia\Inertia;
use App\Models\Entidad;


class MembresiasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $membresias = Membresia::with('entidad')->paginate(10);

        return inertia('Membresias/Index', ['membresias' => $membresias]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $entidades = Entidad::select('id','nombre')->get();

        return inertia('Membresias/Create', [
            'entidades' => $entidades
        ]);
    }

       /**
     * Store a newly created resource in storage.
     * @param App\Http\Requests\MembresiaRequest
     * @param \Illuminate\Http\Response
     */
    public function store(MembresiaRequest $request)
    {
        Membresia::create($request->validated());
        return redirect()->route('membresias.index');
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
    public function edit(Membresia $membresia)
    {
        $entidades = Entidad::select('id','nombre')->get();

        // Devolver la vista de edición
        return Inertia::render('Membresias/Edit', ['membresia' => $membresia, 'entidades' => $entidades]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MembresiaRequest $request, $id)
    {
        $membresia = Membresia::findOrFail($id);

        $membresia->update($request->validated());

        return redirect()->route('membresias.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $membresia = Membresia::findorfail($id);
            $membresia->delete();
            return redirect()->route('membresias.index')->with('success', 'Membresía eliminada con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('membresias.index')->with('error', 'Error al eliminar la Membresía: ' . $e->getMessage());
        }
    }
}

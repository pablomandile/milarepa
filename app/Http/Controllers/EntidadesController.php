<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Entidad;
use Inertia\Inertia;
use App\Http\Requests\EntidadRequest;

class EntidadesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $entidades = Entidad::paginate(15);
        return inertia('Entidades/Index', ['entidades' => $entidades]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Entidades/Create');
    }

    /**
     * Store a newly created resource in storage.
     * @param App\Http\Requests\EntidadRequest
     * @param \Illuminate\Http\Response
     */
    public function store(EntidadRequest $request)
    {
        Entidad::create($request->validated());
        return redirect()->route('entidades.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

   // Controlador con Route Model Binding
    public function edit($id)
    {
        // Obtener el dispo a editar
        $entidad = Entidad::findOrFail($id);

        // Devolver la vista de edición
        return Inertia::render('Entidades/Edit', [
            'entidad' => $entidad,
        ]);
    }

    public function update(EntidadRequest $request, $id)
    {
        $entidad = Entidad::findOrFail($id);

        $entidad->update($request->validated());

        return redirect()->route('entidades.index');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $entidad = Entidad::findorfail($id);
            $entidad->delete();
            return redirect()->route('entidades.index')->with('success', 'Entidad eliminada con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('entidades.index')->with('error', 'Error al eliminar la Entidad: ' . $e->getMessage());
        }
    }
}
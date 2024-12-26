<?php

namespace App\Http\Controllers;

use App\Http\Requests\ModalidadRequest;
use Illuminate\Http\Request;
use App\Models\Modalidad;
use Inertia\Inertia;

class ModalidadesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $modalidades = Modalidad::paginate(15);
        return inertia('Modalidades/Index', ['modalidades' => $modalidades]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Modalidades/Create');
    }

    /**
     * Store a newly created resource in storage.
     * @param App\Http\Requests\ModalidadRequest
     * @param \Illuminate\Http\Response
     */
    public function store(ModalidadRequest $request)
    {
        Modalidad::create($request->validated());
        return redirect()->route('modalidades.index');
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
     * @param int $id
     * @return \Illuminate\Http\Response
     */

    public function edit(Modalidad $modalidad)
    {
        // Devolver la vista de edición
        return Inertia::render('Modalidades/Edit', [
            'modalidad' => $modalidad,
        ]);
    }

    /**
     * Update the specified resource in storage.
     * @param App\Http\Requests\ModalidadRequest $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(ModalidadRequest $request, $id)
    {
        $disponibilidad = Modalidad::findOrFail($id);

        $disponibilidad->update($request->validated());

        return redirect()->route('modalidades.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        // dd($disponibilidad->id);
        try {
            $disponibilidad = Modalidad::findorfail($id);
            $disponibilidad->delete();
            return redirect()->route('modalidades.index')->with('success', 'Modalidad eliminada con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('modalidades.index')->with('error', 'Error al eliminar la Modalidad: ' . $e->getMessage());
        }
    }
}

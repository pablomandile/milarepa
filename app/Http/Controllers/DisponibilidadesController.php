<?php

namespace App\Http\Controllers;

use App\Http\Requests\DisponibilidadRequest;
use Illuminate\Http\Request;
use App\Models\Disponibilidad;
use Inertia\Response;

class DisponibilidadesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $disponibilidades = Disponibilidad::paginate(15);
        return inertia('Disponibilidades/Index', ['disponibilidades' => $disponibilidades]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('Disponibilidades/Create');
    }

    /**
     * Store a newly created resource in storage.
     * @param App\Http\Requests\DisponibilidadRequest
     * @param \Illuminate\Http\Response
     */
    public function store(DisponibilidadRequest $request)
    {
        Disponibilidad::create($request->validated());
        return redirect()->route('disponibilidades.index');
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
    // public function edit(Disponibilidad $disponibilidad)
    // {
    //     if ($disponibilidad) {
    //         dump($disponibilidad);
    //     }
    //     return inertia('Disponibilidades/Edit', ['disponibilidad' => $disponibilidad]);
    // }
    public function edit($id)
    {
        // dd(gettype($id), $id);

        $disponibilidad = Disponibilidad::find($id);

        return inertia('Disponibilidades/Edit', ['disponibilidad' => $disponibilidad]);
    }

    /**
     * Update the specified resource in storage.
     * @param App\Http\Requests\DisponibilidadRequest $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(DisponibilidadRequest $request, $id)
    {
        $disponibilidad = Disponibilidad::findOrFail($id);

        $disponibilidad->update($request->validated());

        return redirect()->route('disponibilidades.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Disponibilidad  $disponibilidad)
    {
        $disponibilidad->delete();
        return redirect()->route('disponibilidades.index');
    }
}

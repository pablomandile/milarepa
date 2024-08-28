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
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Coordinador;

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
        return inertia('Maestros/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Coordinador::create($request->validated());
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

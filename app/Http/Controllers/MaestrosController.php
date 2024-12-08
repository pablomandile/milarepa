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

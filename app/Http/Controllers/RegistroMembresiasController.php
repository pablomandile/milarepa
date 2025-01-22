<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\users;
use App\Http\Requests\ComidaRequest;
use App\Models\Membresia;
use Inertia\Inertia;


class RegistroMembresiasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $membresias = Membresia::all();
        return inertia('RegistroMembresias/Index', ['membresias' => $membresias]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return inertia('RegistroMembresias/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store($request)
    {

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

    }

    /**
     * Update the specified resource in storage.
     */
    public function update($request, $id)
    {

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy( $id)
    {

    }
}

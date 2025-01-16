<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            // Guarda el archivo en public/actividades
            $path = $file->store('actividades', 'public');

            // Retorna la URL pública del archivo
            return response()->json(['filePath' => Storage::url($path)], 200);
        }

        // Retorna error si no se envió un archivo
        return response()->json(['error' => 'No file uploaded'], 400);
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
    public function edit( )
    {

    }

    /**
     * Update the specified resource in storage.
     */
    public function update( )
    {

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy()
    {
       
    }
}

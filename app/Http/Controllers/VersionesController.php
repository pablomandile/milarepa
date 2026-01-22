<?php

namespace App\Http\Controllers;

use App\Models\Version;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class VersionesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $versiones = Version::orderBy('created_at', 'desc')->get();

        return Inertia::render('Versiones/Index', [
            'versiones' => $versiones
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Versiones/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'version' => 'required|string|max:50|unique:versiones,version',
        ], [
            'version.required' => 'La versión es obligatoria.',
            'version.unique' => 'Esta versión ya existe en el sistema.',
            'version.max' => 'La versión no puede tener más de 50 caracteres.',
        ]);

        Version::create($validated);

        return redirect()->route('versiones.index')
            ->with('success', 'Versión creada exitosamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Version $version): Response
    {
        return Inertia::render('Versiones/Edit', [
            'version' => $version
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Version $version): RedirectResponse
    {
        $validated = $request->validate([
            'version' => 'required|string|max:50|unique:versiones,version,' . $version->id,
        ], [
            'version.required' => 'La versión es obligatoria.',
            'version.unique' => 'Esta versión ya existe en el sistema.',
            'version.max' => 'La versión no puede tener más de 50 caracteres.',
        ]);

        $version->update($validated);

        return redirect()->route('versiones.index')
            ->with('success', 'Versión actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Version $version): RedirectResponse
    {
        $version->delete();

        return redirect()->route('versiones.index')
            ->with('success', 'Versión eliminada exitosamente.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Tutorial;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TutorialesController extends Controller
{
    public function index()
    {
        $tutoriales = Tutorial::orderBy('created_at', 'desc')->get();
        return inertia('Tutoriales/Index', ['tutoriales' => $tutoriales]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:500', 'url'],
            'descripcion' => ['required', 'string', 'max:255'],
        ]);

        if (!Tutorial::extractYoutubeId($validated['url'])) {
            return back()->withErrors(['url' => 'La URL debe ser un enlace válido de YouTube.'])->withInput();
        }

        Tutorial::create($validated);

        return redirect()->route('tutoriales.index')->with('success', 'Tutorial agregado correctamente.');
    }

    public function update(Request $request, Tutorial $tutorial)
    {
        $validated = $request->validate([
            'url' => ['required', 'string', 'max:500', 'url'],
            'descripcion' => ['required', 'string', 'max:255'],
        ]);

        if (!Tutorial::extractYoutubeId($validated['url'])) {
            return back()->withErrors(['url' => 'La URL debe ser un enlace válido de YouTube.'])->withInput();
        }

        $tutorial->update($validated);

        return redirect()->route('tutoriales.index')->with('success', 'Tutorial actualizado correctamente.');
    }

    public function destroy(Tutorial $tutorial)
    {
        try {
            $tutorial->delete();
            return redirect()->route('tutoriales.index')->with('success', 'Tutorial eliminado correctamente.');
        } catch (\Exception $e) {
            return redirect()->route('tutoriales.index')->with('error', 'Error al eliminar el tutorial: ' . $e->getMessage());
        }
    }
}

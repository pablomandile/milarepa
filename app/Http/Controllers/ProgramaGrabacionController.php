<?php

namespace App\Http\Controllers;

use App\Models\ProgramaEstudio;
use App\Models\ProgramaGrabacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProgramaGrabacionController extends Controller
{
    public function index()
    {
        $programaEstudios = ProgramaEstudio::query()
            ->with(['programaGrabaciones' => function ($q) {
                $q->orderByDesc('created_at');
            }])
            ->orderBy('nombre')
            ->get();

        return Inertia::render('ProgramaGrabaciones/Index', [
            'programaEstudios' => $programaEstudios,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'programa_estudio_id' => ['required', 'integer', 'exists:programa_estudios,id'],
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'archivo' => ['required', 'file', 'mimes:mp3,mpga', 'max:307200'],
        ]);

        $archivo = $request->file('archivo');
        $path = $archivo->store('programa-grabaciones', 'public');

        ProgramaGrabacion::create([
            'programa_estudio_id' => $validated['programa_estudio_id'],
            'nombre' => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
            'archivo' => $path,
            'size_bytes' => $archivo->getSize(),
            'mime_type' => $archivo->getMimeType(),
        ]);

        return redirect()->route('programa-grabaciones.index')
            ->with('success', 'Grabación subida con éxito.');
    }

    public function destroy(ProgramaGrabacion $programaGrabacion)
    {
        try {
            if ($programaGrabacion->archivo && Storage::disk('public')->exists($programaGrabacion->archivo)) {
                Storage::disk('public')->delete($programaGrabacion->archivo);
            }
            $programaGrabacion->delete();

            return redirect()->route('programa-grabaciones.index')
                ->with('success', 'Grabación eliminada con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('programa-grabaciones.index')
                ->with('error', 'Error al eliminar la grabación: ' . $e->getMessage());
        }
    }
}

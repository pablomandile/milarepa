<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProgramaEstudioRequest;
use App\Models\ProgramaEstudio;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProgramaEstudiosController extends Controller
{
    public function index()
    {
        $programaEstudios = ProgramaEstudio::orderBy('nombre')->get();

        return inertia('ProgramaEstudios/Index', [
            'programaEstudios' => $programaEstudios,
        ]);
    }

    public function create()
    {
        return inertia('ProgramaEstudios/Create');
    }

    public function store(ProgramaEstudioRequest $request)
    {
        $data = $request->validated();
        unset($data['compromisos_pdf'], $data['compromisos_pdf_borrar']);

        if ($request->hasFile('compromisos_pdf')) {
            $data['compromisos_pdf'] = $request->file('compromisos_pdf')->store('programa-compromisos', 'public');
        }

        ProgramaEstudio::create($data);

        return redirect()->route('programa-estudios.index')->with('success', 'Programa de estudio creado con éxito.');
    }

    public function edit(ProgramaEstudio $programaEstudio)
    {
        return Inertia::render('ProgramaEstudios/Edit', [
            'programaEstudio' => $programaEstudio,
        ]);
    }

    public function update(ProgramaEstudioRequest $request, ProgramaEstudio $programaEstudio)
    {
        $data = $request->validated();
        $borrarPdf = (bool) ($data['compromisos_pdf_borrar'] ?? false);
        unset($data['compromisos_pdf'], $data['compromisos_pdf_borrar']);

        if ($request->hasFile('compromisos_pdf')) {
            if ($programaEstudio->compromisos_pdf && Storage::disk('public')->exists($programaEstudio->compromisos_pdf)) {
                Storage::disk('public')->delete($programaEstudio->compromisos_pdf);
            }
            $data['compromisos_pdf'] = $request->file('compromisos_pdf')->store('programa-compromisos', 'public');
        } elseif ($borrarPdf) {
            if ($programaEstudio->compromisos_pdf && Storage::disk('public')->exists($programaEstudio->compromisos_pdf)) {
                Storage::disk('public')->delete($programaEstudio->compromisos_pdf);
            }
            $data['compromisos_pdf'] = null;
        }

        $programaEstudio->update($data);

        return redirect()->route('programa-estudios.index')->with('success', 'Programa de estudio actualizado con éxito.');
    }

    public function destroy(ProgramaEstudio $programaEstudio)
    {
        try {
            if ($programaEstudio->compromisos_pdf && Storage::disk('public')->exists($programaEstudio->compromisos_pdf)) {
                Storage::disk('public')->delete($programaEstudio->compromisos_pdf);
            }
            $programaEstudio->delete();
            return redirect()->route('programa-estudios.index')->with('success', 'Programa de estudio eliminado con éxito.');
        } catch (\Exception $e) {
            return redirect()->route('programa-estudios.index')->with('error', 'Error al eliminar el programa de estudio: ' . $e->getMessage());
        }
    }

    public function asignacionUsuarios()
    {
        $users = User::query()
            ->with(['programaEstudio:id,nombre,abreviacion'])
            ->select('id', 'name', 'email', 'programa_estudio_id', 'programa_a_distancia')
            ->orderBy('name')
            ->get();

        $programaEstudios = ProgramaEstudio::query()
            ->select('id', 'nombre', 'abreviacion')
            ->orderBy('nombre')
            ->get();

        return Inertia::render('ProgramaEstudios/AsignacionUsuarios', [
            'users' => $users,
            'programaEstudios' => $programaEstudios,
        ]);
    }

    public function actualizarAsignacionUsuario(Request $request, User $user)
    {
        $validated = $request->validate([
            'programa_estudio_id' => ['nullable', 'integer', 'exists:programa_estudios,id'],
            'programa_a_distancia' => ['nullable', 'boolean'],
        ]);

        $programaEstudioId = $validated['programa_estudio_id'] ?? null;
        // Si no hay programa, programa_a_distancia debe quedar null (no aplica).
        $aDistancia = $programaEstudioId === null
            ? null
            : (bool) ($validated['programa_a_distancia'] ?? false);

        $user->update([
            'programa_estudio_id' => $programaEstudioId,
            'programa_a_distancia' => $aDistancia,
        ]);

        return back()->with('success', 'Programa de estudio actualizado para ' . $user->name . '.');
    }
}

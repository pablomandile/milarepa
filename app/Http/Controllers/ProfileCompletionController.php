<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Models\Provincia;
use App\Models\Municipio;
use App\Models\Barrio;
use App\Models\Membresia;
use App\Models\Pais;
use App\Models\Sexo;
use App\Models\ProgramaEstudio;
use Inertia\Inertia;

class ProfileCompletionController extends Controller
{
    public function create()
    {
        $membresias = Membresia::with('entidad')->get()
        ->map(function($m){
            $m->label = $m->nombre . ' - ' . ($m->entidad->abreviacion ?? '');
            return $m;
        });
        $paises = Pais::all();
        $provincias = Provincia::orderByRaw('FIELD(id, 24) DESC, id ASC')->get();
        $municipios = Municipio::all();
        $barrios = Barrio::all();
        $sexos = Sexo::all();
        $programas = ProgramaEstudio::orderBy('nombre')->get();
        return inertia('Profile/CompleteProfile', [
            'membresias'=>$membresias,
            'paises'=>$paises,
            'provincias'=>$provincias,
            'municipios'=>$municipios,
            'barrios'=>$barrios,
            'sexos'=>$sexos,
            'programa_estudios' => $programas
        ]); 
    }

    public function store(ProfileRequest $request)
    {
        // Actualizar el usuario logueado
        $user = auth()->user();
        $user->update($request->validated());

        // Redirigir a donde gustes, por ejemplo al dashboard
        return redirect()->route('dashboard')
            ->with('success', '¡Perfil completado con éxito!');
    }

    public function edit()
    {
        return Inertia::render('Profile/CompleteProfile', [
            'user' => auth()->user(),
            'membresias' => Membresia::with('entidad')->get()
            ->map(function($m){
                $m->label = $m->nombre . ' - ' . ($m->entidad->abreviacion ?? '');
                return $m;
            }),
            'paises' => Pais::all(),
            'provincias' => Provincia::orderByRaw('FIELD(id, 24) DESC, id ASC')->get(),
            'municipios' => Municipio::all(),
            'barrios' => Barrio::all(),
            'sexos' => Sexo::all(),
            'programa_estudios' => ProgramaEstudio::orderBy('nombre')->get(),
            'updating' => true, 
        ]);
    }

    public function update(ProfileRequest $request)
    {

        $user = $request->user();
        $user->update($request->validated());

        return redirect()->route('profile.show')
            ->with('success', 'Datos adicionales actualizados.');
    }

}

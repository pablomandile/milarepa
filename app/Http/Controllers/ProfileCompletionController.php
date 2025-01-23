<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Models\Localidad;
use App\Models\Membresia;
use App\Models\Pais;
use App\Models\Sexo;
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
        $localidades = Localidad::all();
        $sexos = Sexo::all();
        return inertia('Profile/CompleteProfile', [
            'membresias'=>$membresias,
            'paises'=>$paises,
            'localidades'=>$localidades,
            'sexos'=>$sexos
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
            'localidades' => Localidad::all(),
            'sexos' => Sexo::all(),
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

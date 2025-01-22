<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Models\Localidad;
use Illuminate\Http\Request;
use App\Models\Membresia;
use App\Models\Pais;
use App\Models\Sexo;

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
}

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
use App\Models\User;
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
        /** @var User $user */
        $user = auth()->user();
        $validated = $request->validated();
        $user->update($validated);

        $this->syncMembresiaUsuarioDesdePerfil($user, $validated);

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

        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();
        $user->update($validated);

        $this->syncMembresiaUsuarioDesdePerfil($user, $validated);

        return redirect()->route('profile.show')
            ->with('success', 'Datos adicionales actualizados.');
    }

    public function editUser(User $user)
    {
        return Inertia::render('Profile/CompleteProfile', [
            'user' => $user,
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
            'target_user_id' => $user->id,
        ]);
    }

    public function updateUser(ProfileRequest $request, User $user)
    {
        $validated = $request->validated();
        $user->update($validated);

        $this->syncMembresiaUsuarioDesdePerfil($user, $validated);

        return redirect()->route('usuarios.edit', $user->id)
            ->with('success', 'Datos de perfil del usuario actualizados.');
    }

    private function syncMembresiaUsuarioDesdePerfil(User $user, array $validated): void
    {
        if (!array_key_exists('membresia_id', $validated)) {
            return;
        }

        $membresiaId = $validated['membresia_id'] ?? null;
        $fechaInscripcion = $membresiaId
            ? ($user->membresia_inscripcion_fecha ?: now()->toDateString())
            : null;

        $user->updateMembresiaUsuario([
            'membresia_id' => $membresiaId,
            'membresia_inscripcion_fecha' => $fechaInscripcion,
            'membresia_online' => $membresiaId ? (bool) ($user->membresia_online ?? false) : false,
            'membresia_online_motivo' => $membresiaId ? $user->membresia_online_motivo : null,
            'info_tarjetas_kadampa' => (bool) ($user->info_tarjetas_kadampa ?? false),
            'envioInfoTk' => $user->envioInfoTk,
        ]);
    }

}

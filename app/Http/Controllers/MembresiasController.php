<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Membresia;
use App\Http\Requests\MembresiaRequest;
use Inertia\Inertia;
use App\Models\Entidad;
use Carbon\Carbon;
use App\Models\EstadoCuentaMembresia;
use App\Models\BotonPago;
use App\Models\Pais;
use App\Models\Provincia;
use App\Models\Municipio;
use App\Models\Barrio;
use App\Models\User;

class MembresiasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $entidadPrincipal = Entidad::where('entidad_principal', true)->first();

        if ($entidadPrincipal) {
            $membresias = Membresia::with(['entidad', 'botonPago'])
                ->where('entidad_id', $entidadPrincipal->id)
                ->where('nombre', '!=', 'Sin membresia')
                ->paginate(10);
        } else {
            $membresias = Membresia::with(['entidad', 'botonPago'])
                ->where('nombre', '!=', 'Sin membresia')
                ->paginate(10);
        }

        $userMembresia = null;
        $estadoCuenta = null;
        $estadosCuenta = [];
        if (auth()->check() && auth()->user()->membresia_id) {
            $userMembresia = Membresia::with('botonPago')->find(auth()->user()->membresia_id);
            if ($userMembresia) {
                $estadoCuenta = EstadoCuentaMembresia::where('user_id', auth()->id())
                    ->where('membresia_id', $userMembresia->id)
                    ->orderBy('mes_pagado', 'desc')
                    ->orderBy('fecha_pago', 'desc')
                    ->first();
                $estadosCuenta = EstadoCuentaMembresia::where('user_id', auth()->id())
                    ->where('membresia_id', $userMembresia->id)
                    ->orderBy('mes_pagado', 'desc')
                    ->get(['id', 'mes_pagado', 'pagado', 'comprobante']);
            }
        }

        return inertia('Membresias/Index', [
            'membresias' => $membresias,
            'user_membresia' => $userMembresia,
            'estado_cuenta' => $estadoCuenta,
            'estados_cuenta' => $estadosCuenta,
        ]);
    }

    /**
     * Listing publico de membresias.
     */
    public function publicIndex()
    {
        $entidadPrincipal = Entidad::where('entidad_principal', true)->first();

        if ($entidadPrincipal) {
            $membresias = Membresia::with(['entidad', 'botonPago'])
                ->where('entidad_id', $entidadPrincipal->id)
                ->where('nombre', '!=', 'Sin membresia')
                ->paginate(10);
        } else {
            $membresias = Membresia::with(['entidad', 'botonPago'])
                ->where('nombre', '!=', 'Sin membresia')
                ->paginate(10);
        }

        $userMembresia = null;
        $selectedUserId = null;

        if (auth()->check()) {
            $selectedUserId = auth()->id();
        } elseif (request()->filled('user_id')) {
            $selectedUserId = (int) request()->query('user_id');
        }

        if ($selectedUserId) {
            $selectedUser = User::with('membresia.botonPago')->find($selectedUserId);
            if ($selectedUser?->membresia_id) {
                $userMembresia = $selectedUser->membresia;
            }
        }

        return inertia('Membresias/PublicIndex', [
            'membresias' => $membresias,
            'user_membresia' => $userMembresia,
            'selected_user_id' => $selectedUserId,
            'paises' => Pais::all(),
            'provincias' => Provincia::orderByRaw('FIELD(id, 24) DESC, id ASC')->get(),
            'municipios' => Municipio::all(),
            'barrios' => Barrio::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $entidades = Entidad::select('id', 'nombre')->get();
        $botonesPago = BotonPago::select('id', 'nombre')->get();

        return inertia('Membresias/Create', [
            'entidades' => $entidades,
            'botonesPago' => $botonesPago,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MembresiaRequest $request)
    {
        Membresia::create($request->validated());
        return redirect()->route('membresias.gestion')->with('success', 'Membresia creada con exito.');
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
    public function edit(Membresia $membresia)
    {
        $entidades = Entidad::select('id', 'nombre')->get();
        $botonesPago = BotonPago::select('id', 'nombre')->get();

        return Inertia::render('Membresias/Edit', [
            'membresia' => $membresia,
            'entidades' => $entidades,
            'botonesPago' => $botonesPago,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MembresiaRequest $request, $id)
    {
        $membresia = Membresia::findOrFail($id);
        $membresia->update($request->validated());

        return redirect()->route('membresias.gestion')->with('success', 'Membresia actualizada con exito.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $membresia = Membresia::findOrFail($id);
            $membresia->delete();
            return redirect()->route('membresias.index')->with('success', 'Membresia eliminada con exito.');
        } catch (\Exception $e) {
            return redirect()->route('membresias.index')->with('error', 'Error al eliminar la Membresia: ' . $e->getMessage());
        }
    }

    /**
     * Display a listing of the resource for admin management.
     */
    public function gestion()
    {
        $membresias = Membresia::with(['entidad', 'botonPago'])->paginate(10);

        return inertia('Membresias/Gestion', ['membresias' => $membresias]);
    }

    /**
     * Subscribe authenticated user.
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'membresia_id' => 'required|exists:membresias,id',
            'modalidad' => 'required|in:PRESENCIAL,ONLINE',
            'motivo_online' => 'nullable|string|max:255',
        ]);

        $user = auth()->user();
        $this->asignarMembresiaAUsuario(
            $user,
            (int) $request->membresia_id,
            (string) $request->modalidad,
            $request->motivo_online
        );

        return redirect()->route('membresias.index')->with('success', 'Te has inscrito correctamente a la membresia');
    }

    /**
     * Subscribe public user (logged or guest with form).
     */
    public function subscribePublic(Request $request)
    {
        $validated = $request->validate([
            'membresia_id' => ['required', 'exists:membresias,id'],
            'modalidad' => ['required', 'in:PRESENCIAL,ONLINE'],
            'motivo_online' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'exists:users,id'],
            'guest' => ['nullable', 'array'],
            'guest.name' => ['required_without:user_id', 'string', 'max:255'],
            'guest.email' => ['required_without:user_id', 'email', 'max:255'],
            'guest.telefono' => ['nullable', 'string', 'max:50'],
            'guest.whatsapp' => ['nullable', 'string', 'max:50'],
            'guest.pais_id' => ['required_without:user_id', 'exists:paises,id'],
            'guest.provincia_id' => ['required_without:user_id', 'exists:provincias,id'],
            'guest.municipio_id' => ['nullable', 'exists:municipios,id'],
            'guest.barrio_id' => ['nullable', 'exists:barrios,id'],
            'guest.direccion' => ['nullable', 'string', 'max:255'],
            'guest.msgxmail' => ['nullable', 'boolean'],
            'guest.msgxwapp' => ['nullable', 'boolean'],
            'guest.accesibilidad' => ['nullable', 'boolean'],
            'guest.accesibilidad_desc' => ['nullable', 'string', 'max:255'],
            'guest.registrar_datos' => ['nullable', 'boolean'],
        ]);

        $user = null;
        if (auth()->check()) {
            $user = auth()->user();
        } elseif (!empty($validated['user_id'])) {
            $user = User::findOrFail($validated['user_id']);
        } else {
            $guest = $validated['guest'] ?? [];
            $user = User::firstOrCreate(
                ['email' => $guest['email']],
                [
                    'name' => $guest['name'],
                    'password' => null,
                ]
            );

            $user->update([
                'name' => $guest['name'],
                'telefono' => $guest['telefono'] ?? null,
                'whatsapp' => $guest['whatsapp'] ?? null,
                'pais_id' => $guest['pais_id'] ?? null,
                'provincia_id' => $guest['provincia_id'] ?? null,
                'municipio_id' => $guest['municipio_id'] ?? null,
                'barrio_id' => $guest['barrio_id'] ?? null,
                'direccion' => $guest['direccion'] ?? null,
                'msgxmail' => (bool) ($guest['msgxmail'] ?? false),
                'msgxwapp' => (bool) ($guest['msgxwapp'] ?? false),
                'accesibilidad' => (bool) ($guest['accesibilidad'] ?? false),
                'accesibilidad_desc' => $guest['accesibilidad_desc'] ?? null,
            ]);
        }

        $this->asignarMembresiaAUsuario(
            $user,
            (int) $validated['membresia_id'],
            (string) $validated['modalidad'],
            $validated['motivo_online'] ?? null
        );

        return response()->json([
            'ok' => true,
            'message' => 'Te has inscrito correctamente a la membresia.',
            'user_id' => $user->id,
        ]);
    }

    private function asignarMembresiaAUsuario(User $user, int $membresiaId, string $modalidad, ?string $motivoOnline = null): void
    {
        $membresiaOnline = $modalidad === 'ONLINE';
        $user->update([
            'membresia_id' => $membresiaId,
            'membresia_online' => $membresiaOnline,
            'membresia_online_motivo' => $membresiaOnline ? $motivoOnline : null,
        ]);

        $mesPagado = Carbon::now()->format('Y-m');
        $existe = EstadoCuentaMembresia::where('user_id', $user->id)
            ->where('membresia_id', $membresiaId)
            ->where('mes_pagado', $mesPagado)
            ->exists();

        if ($existe) {
            return;
        }

        $importe = optional($user->membresia)->valor ?? 0;
        EstadoCuentaMembresia::create([
            'user_id' => $user->id,
            'membresia_id' => $membresiaId,
            'mes_pagado' => $mesPagado,
            'fecha_pago' => null,
            'importe' => $importe,
            'pagado' => false,
            'estado' => EstadoCuentaMembresia::ESTADO_ACTIVA,
            'observaciones' => 'Inscripcion realizada por ' . ($user->name ?? 'sistema'),
        ]);
    }
}

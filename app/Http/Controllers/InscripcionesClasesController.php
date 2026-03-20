<?php

namespace App\Http\Controllers;

use App\Http\Requests\InscripcionClaseRequest;
use App\Models\Barrio;
use App\Models\Clase;
use App\Models\EstadoCuentaMembresia;
use App\Models\Entidad;
use App\Models\GuestUser;
use App\Models\InscripcionClase;
use App\Models\Municipio;
use App\Models\Provincia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InscripcionesClasesController extends Controller
{
    public function index()
    {
        $inscripcionesClases = InscripcionClase::with([
            'clase:id,nombre',
            'user:id,name,email',
            'guestUser:id,name,email',
        ])->latest('id')->get();

        return inertia('InscripcionesClases/Index', [
            'inscripcionesClases' => $inscripcionesClases,
        ]);
    }

    public function create()
    {
        return inertia('InscripcionesClases/Create', [
            'clases' => $this->obtenerClasesDisponibles(),
            'entidades' => Entidad::orderBy('nombre')->get(['id', 'nombre']),
            'provincias' => Provincia::orderByRaw('FIELD(id, 24) DESC, id ASC')->get(['id', 'nombre']),
            'municipios' => Municipio::orderBy('nombre')->get(['id', 'nombre', 'provincia_id']),
            'barrios' => Barrio::orderBy('nombre')->get(['id', 'nombre', 'provincia_id']),
        ]);
    }

    public function edit(InscripcionClase $inscripciones_clase)
    {
        $inscripcion = $inscripciones_clase->load([
            'user:id,name,email,provincia_id,municipio_id,barrio_id,direccion,telefono',
            'guestUser:id,name,email,provincia_id,municipio_id,barrio_id,direccion,telefono',
            'clase:id,nombre,esquema_precio_id,entidad_id',
        ]);

        return inertia('InscripcionesClases/Edit', [
            'inscripcionClase' => $inscripcion,
            'clases' => $this->obtenerClasesDisponibles($inscripcion->clase_id),
            'entidades' => Entidad::orderBy('nombre')->get(['id', 'nombre']),
            'provincias' => Provincia::orderByRaw('FIELD(id, 24) DESC, id ASC')->get(['id', 'nombre']),
            'municipios' => Municipio::orderBy('nombre')->get(['id', 'nombre', 'provincia_id']),
            'barrios' => Barrio::orderBy('nombre')->get(['id', 'nombre', 'provincia_id']),
        ]);
    }

    public function store(InscripcionClaseRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $this->persistirInscripcionClase($validated);
        });

        return redirect()->route('inscripciones-clases.index')->with('success', 'Inscripción de clase registrada correctamente.');
    }

    public function update(InscripcionClaseRequest $request, InscripcionClase $inscripciones_clase)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated, $inscripciones_clase) {
            $this->persistirInscripcionClase($validated, $inscripciones_clase);
        });

        return redirect()->route('inscripciones-clases.index')->with('success', 'Inscripción de clase actualizada correctamente.');
    }

    public function destroy(InscripcionClase $inscripciones_clase)
    {
        $inscripciones_clase->delete();

        return redirect()->route('inscripciones-clases.index')->with('success', 'Inscripción de clase eliminada correctamente.');
    }

    public function buscarUsuario(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->string('email')->toString())
            ->first(['id', 'name', 'email', 'provincia_id', 'municipio_id', 'barrio_id', 'direccion', 'telefono']);

        $membresiaActiva = null;

        if ($user) {
            $estadoActivo = EstadoCuentaMembresia::query()
                ->with('membresia:id,nombre')
                ->where('user_id', $user->id)
                ->where('estado', EstadoCuentaMembresia::ESTADO_ACTIVA)
                ->orderByDesc('mes_pagado')
                ->orderByDesc('created_at')
                ->first();

            $membresiaActiva = $estadoActivo?->membresia?->nombre;
        }

        return response()->json([
            'found' => (bool) $user,
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'provincia_id' => $user->provincia_id,
                'municipio_id' => $user->municipio_id,
                'barrio_id' => $user->barrio_id,
                'direccion' => $user->direccion,
                'telefono' => $user->telefono,
                'membresia_activa' => $membresiaActiva,
            ] : null,
        ]);
    }

    public function preciosPorClase(Request $request)
    {
        $request->validate([
            'clase_id' => ['required', 'integer', 'exists:clases,id'],
        ]);

        $clase = Clase::with([
            'esquemaPrecio.membresias.membresia:id,nombre',
        ])->findOrFail($request->integer('clase_id'));

        $datosPrecio = $this->resolverDatosPreciosClase($clase);

        return response()->json($datosPrecio);
    }

    private function persistirInscripcionClase(array $validated, ?InscripcionClase $inscripcionClase = null): void
    {
        $email = Str::lower(trim((string) ($validated['email'] ?? '')));
        $registrarDatos = (bool) ($validated['registrar_datos'] ?? false);

        $clase = Clase::with([
            'esquemaPrecio.membresias.membresia:id,nombre',
        ])->findOrFail($validated['clase_id']);

        $datosPrecio = $this->resolverDatosPreciosClase($clase);

        $membresiaSeleccionada = trim((string) $validated['membresia']);
        $montoActividad = $this->resolverMontoActividadPorMembresia($datosPrecio['membresias'], $membresiaSeleccionada);

        $montoTharpa = (float) ($validated['montoTharpa'] ?? 0);
        $montoTienda = (float) ($validated['montoTienda'] ?? 0);
        $montoApagar = $montoActividad + $montoTharpa + $montoTienda;

        $existingUser = User::where('email', $email)->first();
        $user = $existingUser;
        $guestUser = null;

        if (!$user) {
            if ($registrarDatos) {
                $user = User::create([
                    'name' => $validated['nombre'] ?? 'Sin nombre',
                    'email' => $email,
                    'password' => Hash::make(Str::random(24)),
                    'provincia_id' => $validated['provincia_id'] ?? null,
                    'municipio_id' => $validated['municipio_id'] ?? null,
                    'barrio_id' => $validated['barrio_id'] ?? null,
                    'direccion' => $validated['direccion'] ?? null,
                    'telefono' => $validated['telefono'] ?? null,
                ]);
            } else {
                $guestUser = GuestUser::create([
                    'name' => $validated['nombre'] ?? 'Sin nombre',
                    'email' => $email,
                    'provincia_id' => $validated['provincia_id'] ?? null,
                    'municipio_id' => $validated['municipio_id'] ?? null,
                    'barrio_id' => $validated['barrio_id'] ?? null,
                    'direccion' => $validated['direccion'] ?? null,
                    'telefono' => $validated['telefono'] ?? null,
                ]);
            }
        }

        $userId = $user?->id;

        if ($userId !== null) {
            $duplicada = InscripcionClase::query()
                ->where('clase_id', $validated['clase_id'])
                ->where('user_id', $userId)
                ->when($inscripcionClase, fn ($q) => $q->where('id', '!=', $inscripcionClase->id))
                ->exists();

            if ($duplicada) {
                throw ValidationException::withMessages([
                    'email' => 'El usuario ya está inscripto en esta clase.',
                ]);
            }
        }

        $payload = [
            'clase_id' => $validated['clase_id'],
            'user_id' => $userId,
            'guest_user_id' => $userId ? null : $guestUser?->id,
            'nombre_snapshot' => $user?->name ?? $guestUser?->name ?? ($validated['nombre'] ?? null),
            'email_snapshot' => $user?->email ?? $guestUser?->email ?? $email,
            'membresia' => $membresiaSeleccionada,
            'precioGeneral' => $datosPrecio['precio_general'],
            'montoActividad' => $montoActividad,
            'montoTharpa' => $montoTharpa,
            'montoTienda' => $montoTienda,
            'montoApagar' => $montoApagar,
            'pago' => $validated['pago'],
            'online' => (bool) $validated['online'],
        ];

        if ($inscripcionClase) {
            $inscripcionClase->update($payload);
            return;
        }

        InscripcionClase::create($payload);
    }

    private function resolverDatosPreciosClase(Clase $clase): array
    {
        $lineas = collect($clase->esquemaPrecio?->membresias ?? []);

        $membresias = $lineas
            ->map(function ($linea) {
                return [
                    'nombre' => $linea->membresia?->nombre,
                    'precio' => (float) $linea->precio,
                ];
            })
            ->filter(fn ($linea) => !empty($linea['nombre']))
            ->values();

        $lineaSinMembresia = $membresias->first(function ($linea) {
            return $this->normalizarTexto((string) $linea['nombre']) === 'sin membresia'
                || str_contains($this->normalizarTexto((string) $linea['nombre']), 'sin membresia');
        });

        if (!$lineaSinMembresia) {
            throw ValidationException::withMessages([
                'clase_id' => 'falta precio general para la clase',
            ]);
        }

        return [
            'precio_general' => (float) $lineaSinMembresia['precio'],
            'membresias' => $membresias->all(),
        ];
    }

    private function resolverMontoActividadPorMembresia(array $membresias, string $membresiaSeleccionada): float
    {
        $membresia = collect($membresias)->first(function ($item) use ($membresiaSeleccionada) {
            return $this->normalizarTexto((string) ($item['nombre'] ?? '')) === $this->normalizarTexto($membresiaSeleccionada);
        });

        if (!$membresia) {
            throw ValidationException::withMessages([
                'membresia' => 'La membresía seleccionada no existe en el esquema de precios de la clase.',
            ]);
        }

        return (float) ($membresia['precio'] ?? 0);
    }

    private function normalizarTexto(string $texto): string
    {
        return Str::of($texto)->ascii()->lower()->trim()->value();
    }

    private function obtenerClasesDisponibles(?int $incluirClaseId = null)
    {
        $mesActual = Carbon::now()->format('Y-m');
        $hoy = Carbon::today();
        $finMes = Carbon::now()->endOfMonth();

        $clases = Clase::query()
            ->where(function ($query) use ($mesActual) {
                $query->where(function ($q) use ($mesActual) {
                    $q->where('activa', true)
                        ->where('mes_referencia', $mesActual);
                });
            })
            ->when($incluirClaseId, function ($query) use ($incluirClaseId) {
                $query->orWhere('id', $incluirClaseId);
            })
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'esquema_precio_id', 'entidad_id', 'dias_semana', 'mes_referencia']);

        $mapaDias = [
            1 => 'lunes',
            2 => 'martes',
            3 => 'miercoles',
            4 => 'jueves',
            5 => 'viernes',
            6 => 'sabado',
            7 => 'domingo',
        ];

        return $clases
            ->filter(function ($clase) use ($incluirClaseId, $hoy, $finMes, $mapaDias) {
                if ($incluirClaseId && (int) $clase->id === (int) $incluirClaseId) {
                    return true;
                }

                $diasSemana = collect($clase->dias_semana ?? [])->map(fn ($d) => (string) $d)->values();
                if ($diasSemana->isEmpty()) {
                    return false;
                }

                for ($cursor = $hoy->copy(); $cursor->lte($finMes); $cursor->addDay()) {
                    $diaNombre = $mapaDias[$cursor->dayOfWeekIso] ?? null;
                    if ($diaNombre && $diasSemana->contains($diaNombre)) {
                        return true;
                    }
                }

                return false;
            })
            ->map(fn ($clase) => [
                'id' => $clase->id,
                'nombre' => $clase->nombre,
                'esquema_precio_id' => $clase->esquema_precio_id,
                'entidad_id' => $clase->entidad_id,
            ])
            ->values();
    }
}
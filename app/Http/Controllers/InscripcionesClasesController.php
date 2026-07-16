<?php

namespace App\Http\Controllers;

use App\Http\Requests\InscripcionClaseRequest;
use App\Models\Arte;
use App\Models\Barrio;
use App\Models\Clase;
use App\Models\EstadoCuentaMembresia;
use App\Models\Entidad;
use App\Models\InventarioEntidadLibro;
use App\Models\InscripcionClase;
use App\Models\Libro;
use App\Models\MetodoPago;
use App\Models\Municipio;
use App\Models\Oracion;
use App\Models\Otro;
use App\Models\Provincia;
use App\Models\User;
use App\Services\CobroService;
use App\Services\InscripcionClaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
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
        return inertia('InscripcionesClases/Create', array_merge($this->datosFormulario(), [
            'clases' => $this->obtenerClasesDisponibles(),
        ]));
    }

    public function edit(InscripcionClase $inscripciones_clase)
    {
        $inscripcion = $inscripciones_clase->load([
            'user:id,name,email,provincia_id,municipio_id,barrio_id,direccion,telefono',
            'guestUser:id,name,email,provincia_id,municipio_id,barrio_id,direccion,telefono',
            'clase:id,nombre,esquema_precio_id,entidad_id',
            'items',
        ]);

        return inertia('InscripcionesClases/Edit', array_merge($this->datosFormulario(), [
            'inscripcionClase' => $inscripcion,
            'clases' => $this->obtenerClasesDisponibles($inscripcion->clase_id),
        ]));
    }

    public function store(InscripcionClaseRequest $request, InscripcionClaseService $service)
    {
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated, $service) {
                $inscripcion = $service->persistir($validated, null, auth()->id());
                $this->registrarCobroClase($inscripcion, $validated, $inscripcion->user_id);
            });
        } catch (QueryException $exception) {
            $this->lanzarErrorSiDuplicada($exception);
            throw $exception;
        }

        return redirect()->route('inscripciones-clases.index')->with('success', 'Inscripción de clase registrada correctamente.');
    }

    public function update(InscripcionClaseRequest $request, InscripcionClase $inscripciones_clase, InscripcionClaseService $service)
    {
        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated, $inscripciones_clase, $service) {
                $inscripcion = $service->persistir($validated, $inscripciones_clase, auth()->id());
                $this->registrarCobroClase($inscripcion, $validated, $inscripcion->user_id);
            });
        } catch (QueryException $exception) {
            $this->lanzarErrorSiDuplicada($exception);
            throw $exception;
        }

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

    public function preciosPorClase(Request $request, InscripcionClaseService $service)
    {
        $request->validate([
            'clase_id' => ['required', 'integer', 'exists:clases,id'],
        ]);

        $clase = Clase::with([
            'esquemaPrecio.membresias.membresia:id,nombre',
        ])->findOrFail($request->integer('clase_id'));

        return response()->json($service->resolverDatosPreciosClase($clase));
    }

    public function librosPorEntidad(Request $request)
    {
        $request->validate([
            'entidad_id' => ['required', 'integer', 'exists:entidades,id'],
        ]);

        $entidadId = $request->integer('entidad_id');
        $stocksDisponibles = InventarioEntidadLibro::query()
            ->where('entidad_id', $entidadId)
            ->where('cantidad', '>', 0)
            ->pluck('cantidad', 'libro_id');

        $libros = Libro::query()
            ->whereIn('id', $stocksDisponibles->keys()->all())
            ->orderBy('titulo')
            ->get(['id', 'titulo', 'precio'])
            ->map(function ($libro) use ($stocksDisponibles) {
                $stock = (int) ($stocksDisponibles[(int) $libro->id] ?? 0);

                return [
                    'id' => (int) $libro->id,
                    'titulo' => $libro->titulo,
                    'precio' => (float) ($libro->precio ?? 0),
                    'stock_disponible' => $stock,
                ];
            })
            ->filter(fn ($libro) => (int) $libro['stock_disponible'] > 0)
            ->values();

        return response()->json([
            'libros' => $libros,
        ]);
    }

    /**
     * Registra en el ledger el importe cobrado cuando se marca Saldado/Parcial una
     * inscripción a clase. No recalcula el estado (el operador fija el label a mano).
     */
    private function registrarCobroClase(InscripcionClase $inscripcion, array $validated, ?int $userId): void
    {
        $pago = $validated['pago'] ?? null;
        if (!in_array($pago, ['Saldado', 'Parcial'], true)) {
            return;
        }

        $monto = isset($validated['monto_cobrado']) && $validated['monto_cobrado'] !== null
            ? (float) $validated['monto_cobrado']
            : ($pago === 'Saldado' ? $inscripcion->saldoPendiente() : 0.0);

        if ($monto <= 0) {
            return;
        }

        app(CobroService::class)->registrar($inscripcion, [
            'monto' => $monto,
            'fecha_pago' => now()->toDateString(),
            'metodo_pago_id' => $validated['metodo_pago_id'] ?? null,
            'registrado_por' => $userId,
            'origen' => 'manual',
        ], recalcular: false);
    }

    /** Catálogos (4 categorías) y listas para el formulario de inscripción a clase. */
    private function datosFormulario(): array
    {
        return [
            'entidades' => Entidad::orderBy('nombre')->get(['id', 'nombre']),
            'librosTharpa' => Libro::orderBy('titulo')->get(['id', 'titulo', 'tipo', 'precio']),
            'oracionesTharpa' => Oracion::orderBy('titulo')->get(['id', 'titulo', 'tipo', 'precio']),
            'arteTharpa' => Arte::orderBy('titulo')->get(['id', 'titulo', 'tipo', 'precio']),
            'otrosTharpa' => Otro::orderBy('titulo')->get(['id', 'titulo', 'tipo', 'precio']),
            'provincias' => Provincia::orderByRaw('FIELD(id, 24) DESC, id ASC')->get(['id', 'nombre']),
            'municipios' => Municipio::orderBy('nombre')->get(['id', 'nombre', 'provincia_id']),
            'barrios' => Barrio::orderBy('nombre')->get(['id', 'nombre', 'provincia_id']),
            'metodosPago' => MetodoPago::orderBy('nombre')->get(['id', 'nombre']),
        ];
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

    private function lanzarErrorSiDuplicada(QueryException $exception): void
    {
        $errorInfo = $exception->errorInfo;
        $mysqlCode = (int) ($errorInfo[1] ?? 0);
        $mensaje = (string) ($errorInfo[2] ?? '');

        if ($mysqlCode === 1062 && str_contains($mensaje, 'inscripciones_clases_clase_user_unique')) {
            throw ValidationException::withMessages([
                'email' => 'El usuario ya está inscripto en esta clase.',
            ]);
        }
    }
}

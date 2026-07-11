<?php

namespace App\Http\Controllers;

use App\Models\EstadoCuentaMembresia;
use App\Services\CobroService;
use App\Services\GeneradorEstadosCuentaMembresiaService;
use Illuminate\Support\Facades\Storage;
use App\Models\Membresia;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Carbon\Carbon;

class EstadoCuentaMembresiasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $estadoCuentas = EstadoCuentaMembresia::with(['user.membresiaUsuario', 'membresia', 'membresia.entidad'])
            ->orderBy('mes_pagado', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $ultimoMesGenerado = EstadoCuentaMembresia::max('mes_pagado');
        $mesActual = Carbon::now()->format('Y-m');

        if ($ultimoMesGenerado) {
            $siguienteAlUltimo = Carbon::createFromFormat('Y-m', $ultimoMesGenerado)
                ->addMonth()
                ->format('Y-m');
            $mesProximo = max($siguienteAlUltimo, $mesActual);
        } else {
            $mesProximo = $mesActual;
        }

        return inertia('EstadoCuentaMembresias/Index', [
            'estadoCuentas' => $estadoCuentas,
            'mesProximo' => $mesProximo,
            'ultimoMesGenerado' => $ultimoMesGenerado,
        ]);
    }

    public function generar(Request $request, GeneradorEstadosCuentaMembresiaService $service)
    {
        $validated = $request->validate([
            'mes_pagado' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ]);

        $stats = $service->generarParaMes($validated['mes_pagado'], auth()->id());

        $mensaje = sprintf(
            'Mes %s generado: %d creados, %d actualizados por suscripción, %d expirados (%d usuarios procesados).',
            $validated['mes_pagado'],
            $stats['creados'],
            $stats['actualizados'],
            $stats['expirados'],
            $stats['usuarios_procesados']
        );

        return redirect()->back()->with('success', $mensaje);
    }

    public function revertir(GeneradorEstadosCuentaMembresiaService $service)
    {
        $resultado = $service->revertirUltimaGeneracion();

        $tipo = $resultado['ok'] ? 'success' : 'error';

        return redirect()->back()->with($tipo, $resultado['mensaje']);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(EstadoCuentaMembresia $estadoCuentaMembresia)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EstadoCuentaMembresia $estadoCuentaMembresia)
    {
        return inertia('EstadoCuentaMembresias/Edit', [
            'estadoCuenta' => $estadoCuentaMembresia->load(['user.membresiaUsuario', 'membresia', 'membresia.entidad'])
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EstadoCuentaMembresia $estadoCuentaMembresia, CobroService $cobroService)
    {
        $validated = $request->validate([
            'pagado' => 'required|boolean',
            'fecha_pago' => 'nullable|date',
            'observaciones' => 'nullable|string|max:255',
            'modo' => ['nullable', Rule::in(EstadoCuentaMembresia::MODOS_PAGO)],
            'info_pago' => 'nullable|string|max:255',
            'modalidad' => ['nullable', Rule::in(['online', 'presencial'])],
            'estado_activo' => 'required|boolean',
        ]);

        if ($validated['pagado'] && empty($validated['fecha_pago'])) {
            $validated['fecha_pago'] = Carbon::today()->toDateString();
        }

        $validated['estado'] = $validated['estado_activo']
            ? EstadoCuentaMembresia::ESTADO_ACTIVA
            : EstadoCuentaMembresia::ESTADO_EXPIRADA;

        unset($validated['estado_activo']);

        if (!empty($validated['modalidad']) && $estadoCuentaMembresia->user) {
            $usuario = $estadoCuentaMembresia->user;
            $usuario->updateMembresiaUsuario([
                'membresia_id' => $usuario->membresia_id,
                'membresia_inscripcion_fecha' => $usuario->membresia_inscripcion_fecha,
                'membresia_online' => $validated['modalidad'] === 'online',
                'membresia_online_motivo' => $usuario->membresia_online_motivo,
                'info_tarjetas_kadampa' => (bool) ($usuario->info_tarjetas_kadampa ?? false),
                'envioInfoTk' => $usuario->envioInfoTk,
            ]);
        }

        unset($validated['modalidad']);

        $estadoCuentaMembresia->update($validated);

        $cobroService->sincronizarMembresia($estadoCuentaMembresia);

        return redirect()->route('estado-cuenta-membresias.index')
            ->with('success', 'Estado de cuenta actualizado correctamente');
    }

    public function uploadComprobante(Request $request, \App\Services\OptimizadorImagenService $optimizador, CobroService $cobroService)
    {
        $request->validate([
            'comprobante' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
            'estado_cuenta_id' => ['nullable', 'exists:estado_cuenta_membresias,id'],
            'mes_pagado' => ['nullable', 'date_format:Y-m'],
            'modo' => ['nullable', Rule::in(EstadoCuentaMembresia::MODOS_PAGO)],
        ], [
            'comprobante.max' => 'El comprobante supera el tamaÃ±o mÃ¡ximo permitido (4 MB).',
            'comprobante.mimes' => 'El comprobante debe ser PDF, JPG o PNG.',
        ]);


        $modo = $request->input('modo', 'Transferencia');
        if (!$request->hasFile('comprobante') && $modo !== 'Efectivo') {
            return response()->json([
                'ok' => false,
                'message' => 'Subi un comprobante o marca que pagaste en efectivo.',
            ], 422);
        }
        $user = $request->user()->loadMissing(['membresia', 'membresiaUsuario']);
        $userId = $user->id;
        $estadoCuenta = null;
        if ($request->filled('mes_pagado')) {
            $membresiaId = $user->membresia_id;
            if (!$membresiaId) {
                return response()->json([
                    'ok' => false,
                    'message' => 'No tiene una membresia activa para imputar el pago.',
                ], 422);
            }
            $estadoCuenta = EstadoCuentaMembresia::where('user_id', $userId)
                ->where('membresia_id', $membresiaId)
                ->where('mes_pagado', $request->mes_pagado)
                ->first();
            if ($estadoCuenta && $estadoCuenta->pagado) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Ya tiene el pago registrado para ese mes.',
                ], 422);
            }
            if (!$estadoCuenta) {
                $importe = optional($user->membresia)->valor ?? 0;
                $estadoCuenta = EstadoCuentaMembresia::create([
                    'user_id' => $userId,
                    'membresia_id' => $membresiaId,
                    'mes_pagado' => $request->mes_pagado,
                    'fecha_pago' => Carbon::today()->toDateString(),
                    'importe' => $importe,
                    'pagado' => false,
                    'estado' => EstadoCuentaMembresia::ESTADO_ACTIVA,
                    'observaciones' => 'Inscripción realizada por ' . ($request->user()->name ?? 'sistema'),
                    'modo' => $modo,
                ]);
            }
        } elseif ($request->filled('estado_cuenta_id')) {
            $estadoCuenta = EstadoCuentaMembresia::where('id', $request->estado_cuenta_id)
                ->where('user_id', $userId)
                ->firstOrFail();
        } else {
            $estadoCuenta = EstadoCuentaMembresia::where('user_id', $userId)
                ->orderBy('mes_pagado', 'desc')
                ->orderBy('fecha_pago', 'desc')
                ->firstOrFail();
        }

        $path = null;
        if ($request->hasFile('comprobante')) {
            $path = $optimizador->procesar($request->file('comprobante'), 'comprobantes');
            $estadoCuenta->comprobante = $path;
        }
        $estadoCuenta->modo = $modo;
        $estadoCuenta->fecha_pago = Carbon::today()->toDateString();
        $estadoCuenta->save();

        $cobroService->sincronizarMembresia($estadoCuenta);

        return response()->json([
            'ok' => true,
            'path' => $path,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EstadoCuentaMembresia $estadoCuentaMembresia)
    {
        $estadoCuentaMembresia->delete();

        return redirect()->route('estado-cuenta-membresias.index')
            ->with('success', 'Registro eliminado correctamente');
    }

    /**
     * Create monthly account statements for a membership
     */
    public function crearEstadosCuenta($userId, $membresiaId, $fechaInicio, $cantidadMeses = 12, $importe = null)
    {
        // Obsoleto: usar comando membresias:renovar-mensual
        $membresia = Membresia::findOrFail($membresiaId);

        if (!$importe && $membresia->esquemaPrecio) {
            $importe = $membresia->esquemaPrecio->precio ?? 0;
        }

        $fecha = Carbon::parse($fechaInicio);

        $mesPagado = $fecha->format('Y-m');
        $existe = EstadoCuentaMembresia::where('user_id', $userId)
            ->where('membresia_id', $membresiaId)
            ->where('mes_pagado', $mesPagado)
            ->exists();

        if (!$existe) {
            EstadoCuentaMembresia::create([
                'user_id' => $userId,
                'membresia_id' => $membresiaId,
                'mes_pagado' => $mesPagado,
                'fecha_pago' => null,
                'importe' => $importe,
                'pagado' => false,
                'estado' => EstadoCuentaMembresia::ESTADO_ACTIVA,
                'observaciones' => 'Inscripción realizada por sistema'
            ]);
        }
    }
}

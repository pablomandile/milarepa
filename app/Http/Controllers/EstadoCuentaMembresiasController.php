<?php

namespace App\Http\Controllers;

use App\Models\EstadoCuentaMembresia;
use Illuminate\Support\Facades\Storage;
use App\Models\Membresia;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class EstadoCuentaMembresiasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $estadoCuentas = EstadoCuentaMembresia::with(['user', 'membresia', 'membresia.entidad'])
            ->orderBy('mes_pagado', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return inertia('EstadoCuentaMembresias/Index', [
            'estadoCuentas' => $estadoCuentas
        ]);
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
            'estadoCuenta' => $estadoCuentaMembresia->load(['user', 'membresia', 'membresia.entidad'])
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EstadoCuentaMembresia $estadoCuentaMembresia)
    {
        $validated = $request->validate([
            'pagado' => 'required|boolean',
            'fecha_pago' => 'nullable|date',
            'observaciones' => 'nullable|string|max:255',
            'modo' => 'nullable|string|max:100'
        ]);

        if ($validated['pagado'] && empty($validated['fecha_pago'])) {
            $validated['fecha_pago'] = Carbon::today()->toDateString();
        }

        $estadoCuentaMembresia->update($validated);

        return redirect()->route('estado-cuenta-membresias.index')
            ->with('success', 'Estado de cuenta actualizado correctamente');
    }

    public function uploadComprobante(Request $request)
    {
        $request->validate([
            'comprobante' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
            'estado_cuenta_id' => ['nullable', 'exists:estado_cuenta_membresias,id'],
            'mes_pagado' => ['nullable', 'date_format:Y-m'],
        ], [
            'comprobante.max' => 'El comprobante supera el tamaño máximo permitido (4 MB).',
            'comprobante.mimes' => 'El comprobante debe ser PDF, JPG o PNG.',
        ]);

        $userId = $request->user()->id;
        $estadoCuenta = null;
        if ($request->filled('estado_cuenta_id')) {
            $estadoCuenta = EstadoCuentaMembresia::where('id', $request->estado_cuenta_id)
                ->where('user_id', $userId)
                ->firstOrFail();
        } elseif ($request->filled('mes_pagado')) {
            $user = $request->user();
            $membresiaId = $user->membresia_id;
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
                    'fecha_pago' => null,
                    'importe' => $importe,
                    'pagado' => false,
                    'estado' => EstadoCuentaMembresia::ESTADO_ACTIVA,
                    'observaciones' => 'Inscripción realizada por ' . ($request->user()->name ?? 'sistema'),
                    'modo' => 'transferencia',
                ]);
            }
        } else {
            $estadoCuenta = EstadoCuentaMembresia::where('user_id', $userId)
                ->orderBy('mes_pagado', 'desc')
                ->orderBy('fecha_pago', 'desc')
                ->firstOrFail();
        }

        $path = $request->file('comprobante')->store('comprobantes', 'public');
        $estadoCuenta->comprobante = $path;
        $estadoCuenta->save();

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
                'observaciones' => 'Inscripción realizada por ' . ($request->user()->name ?? 'sistema')
            ]);
        }
    }
}

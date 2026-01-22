<?php

namespace App\Http\Controllers;

use App\Models\EstadoCuentaMembresia;
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
            'estadoCuenta' => $estadoCuentaMembresia->load(['membresia', 'membresia.entidad'])
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
            'observaciones' => 'nullable|string|max:255'
        ]);

        $estadoCuentaMembresia->update($validated);

        return redirect()->route('estado-cuenta-membresias.index')
            ->with('success', 'Estado de cuenta actualizado correctamente');
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
        $membresia = Membresia::findOrFail($membresiaId);
        
        if (!$importe && $membresia->esquemaPrecio) {
            $importe = $membresia->esquemaPrecio->precio ?? 0;
        }

        $fecha = Carbon::parse($fechaInicio);

        for ($i = 0; $i < $cantidadMeses; $i++) {
            $mesPagado = $fecha->format('Y-m');

            // Evitar crear duplicados
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
                    'observaciones' => 'Registro automático'
                ]);
            }

            $fecha->addMonth();
        }
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\users;
use App\Http\Requests\ComidaRequest;
use App\Models\Membresia;
use App\Models\EstadoCuentaMembresia;
use Inertia\Inertia;
use Carbon\Carbon;


class RegistroMembresiasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $membresias = Membresia::with(['entidad', 'esquemaPrecioMembresias'])
            ->where('nombre', '!=', 'Sin membresía')
            ->get();
        return inertia('RegistroMembresias/Index', ['membresias' => $membresias]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $membresia = null;
        if ($request->has('membresia_id')) {
            $membresia = Membresia::find($request->membresia_id);
        }

        return inertia('RegistroMembresias/Create', [
            'membresiaSeleccionada' => $membresia
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'membresia_id' => 'required|exists:membresias,id',
            'fecha_inicio' => 'required|date',
            'cantidad_meses' => 'required|integer|min:1|max:36',
            'importe' => 'nullable|numeric|min:0'
        ]);

        $user = auth()->user();
        $membresia = Membresia::findOrFail($validated['membresia_id']);
        
        // Determinar el importe
        $importe = $validated['importe'];
        if (!$importe && $membresia->esquemaPrecioMembresias()->first()) {
            $importe = $membresia->esquemaPrecioMembresias()->first()->precio ?? 0;
        }

        // Crear solo la inscripción del mes actual
        $this->crearEstadosCuenta(
            $user->id,
            $membresia->id,
            $validated['fecha_inicio'],
            $importe
        );

        return redirect()->route('estado-cuenta-membresias.index')
            ->with('success', 'Membresía registrada correctamente. Se han creado los registros mensuales.');
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
    public function edit($id)
    {

    }

    /**
     * Update the specified resource in storage.
     */
    public function update($request, $id)
    {

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy( $id)
    {

    }

    /**
     * Create account statement for current month only
     */
    private function crearEstadosCuenta($userId, $membresiaId, $fechaInicio, $importe = null)
    {
        $membresia = Membresia::findOrFail($membresiaId);
        
        if (!$importe && $membresia->esquemaPrecioMembresias()->first()) {
            $importe = $membresia->esquemaPrecioMembresias()->first()->precio ?? 0;
        }

        $fecha = Carbon::parse($fechaInicio);
        $mesPagado = $fecha->format('Y-m');

        // Solo crear inscripción del mes actual
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
                'importe' => $importe ?? 0,
                'pagado' => false,
                'estado' => EstadoCuentaMembresia::ESTADO_ACTIVA,
                'observaciones' => 'Inscripción realizada por ' . (auth()->user()->name ?? 'sistema')
            ]);
        }
    }
}

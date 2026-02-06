<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Membresia;
use App\Models\EstadoCuentaMembresia;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class MembresiasGestionController extends Controller
{
    public function index()
    {
        $usuarios = User::with('membresia')
            ->orderBy('name')
            ->paginate(15);

        $membresias = Membresia::with('entidad')
            ->select('id', 'nombre', 'entidad_id')
            ->where('nombre', '!=', 'Sin membresía')
            ->orderBy('nombre')
            ->get();

        return inertia('MembresiasGestion/Index', [
            'usuarios' => $usuarios,
            'membresias' => $membresias
        ]);
    }

    public function asignar(Request $request, User $user)
    {
        $validated = $request->validate([
            'membresia_id' => 'nullable|exists:membresias,id'
        ]);

        $membresia = $validated['membresia_id'] ? Membresia::find($validated['membresia_id']) : null;

        $user->update([
            'membresia_id' => $validated['membresia_id'],
            'membresia_inscripcion_fecha' => $validated['membresia_id'] ? now()->toDateString() : null
        ]);

        if ($validated['membresia_id'] && $membresia) {
            $this->crearEstadosCuenta(
                $user->id,
                $validated['membresia_id'],
                now()->toDateString(),
                $membresia->valor ?? 0
            );
        }

        return redirect()->back()
            ->with('success', 'Membresía asignada correctamente a ' . $user->name);
    }

    public function eliminar(User $user)
    {
        // Borrado lógico del estado de cuenta del mes actual (si existe)
        $mesActual = Carbon::now()->format('Y-m');
        $estadoCuenta = EstadoCuentaMembresia::where('user_id', $user->id)
            ->where('membresia_id', $user->membresia_id)
            ->where('mes_pagado', $mesActual)
            ->first();

        if ($estadoCuenta) {
            $estadoCuenta->estado = EstadoCuentaMembresia::ESTADO_EXPIRADA;
            $estadoCuenta->observaciones = trim(($estadoCuenta->observaciones ?? '') . ' | Membresía eliminada');
            $estadoCuenta->save();
        }

        $user->update([
            'membresia_id' => null,
            'membresia_inscripcion_fecha' => null,
            'membresia_online' => false,
            'membresia_online_motivo' => null,
        ]);

        return redirect()->back()
            ->with('success', 'Membresía eliminada correctamente de ' . $user->name);
    }

    private function crearEstadosCuenta($userId, $membresiaId, $fechaInicio, $importe = null)
    {
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
                'importe' => $importe,
                'pagado' => false,
                'estado' => EstadoCuentaMembresia::ESTADO_ACTIVA,
                'observaciones' => 'Inscripción realizada por ' . (auth()->user()->name ?? 'sistema')
            ]);
        }
    }
}

<?php

namespace App\Services;

use App\Models\EstadoCuentaMembresia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GeneradorEstadosCuentaMembresiaService
{
    public function __construct(private CobroService $cobros)
    {
    }

    public function generarParaMes(string $mesPagado, ?int $adminId = null): array
    {
        $admin = $adminId ? User::find($adminId) : null;
        $adminNombre = $admin?->name ?? 'sistema';
        $hoy = Carbon::now()->toDateString();

        $stats = [
            'creados' => 0,
            'actualizados' => 0,
            'expirados' => 0,
            'usuarios_procesados' => 0,
        ];

        User::whereHas('membresiaUsuario', function ($query) {
                $query->whereNotNull('membresia_id');
            })
            ->with(['membresiaUsuario.membresia'])
            ->chunkById(200, function ($users) use ($mesPagado, $hoy, $adminNombre, &$stats) {
                foreach ($users as $user) {
                    $this->procesarUsuario($user, $mesPagado, $hoy, $adminNombre, $stats);
                    $stats['usuarios_procesados']++;
                }
            });

        return $stats;
    }

    protected function procesarUsuario(User $user, string $mesPagado, string $hoy, string $adminNombre, array &$stats): void
    {
        DB::transaction(function () use ($user, $mesPagado, $hoy, $adminNombre, &$stats) {
            $membresiaProfile = $user->membresiaUsuario;
            $membresiaId = $membresiaProfile?->membresia_id;
            if (!$membresiaId) {
                return;
            }

            $esSuscripcion = (bool) $membresiaProfile->suscripcion;
            $importe = (float) ($membresiaProfile->membresia?->valor ?? 0);

            $existente = EstadoCuentaMembresia::query()
                ->where('user_id', $user->id)
                ->where('membresia_id', $membresiaId)
                ->where('mes_pagado', $mesPagado)
                ->latest('id')
                ->first();

            if ($existente) {
                if ($esSuscripcion && !$existente->pagado) {
                    $existente->modo = 'Suscripción';
                    $existente->pagado = true;
                    $existente->fecha_pago = $existente->fecha_pago ?: $hoy;
                    $existente->estado = EstadoCuentaMembresia::ESTADO_ACTIVA;
                    $existente->save();
                    $this->cobros->sincronizarMembresia($existente);
                    $stats['actualizados']++;
                }
                $stats['expirados'] += $this->expirarPreviosDelUsuario($user->id, $mesPagado);
                return;
            }

            $cuota = EstadoCuentaMembresia::create([
                'user_id' => $user->id,
                'membresia_id' => $membresiaId,
                'mes_pagado' => $mesPagado,
                'fecha_pago' => $esSuscripcion ? $hoy : null,
                'importe' => $importe,
                'pagado' => $esSuscripcion,
                'estado' => EstadoCuentaMembresia::ESTADO_ACTIVA,
                'modo' => $esSuscripcion ? 'Suscripción' : null,
                'observaciones' => 'Generado por ' . $adminNombre,
            ]);
            $this->cobros->sincronizarMembresia($cuota);

            $stats['creados']++;
            $stats['expirados'] += $this->expirarPreviosDelUsuario($user->id, $mesPagado);
        });
    }

    private function expirarPreviosDelUsuario(int $userId, string $mesPagado): int
    {
        return EstadoCuentaMembresia::query()
            ->where('user_id', $userId)
            ->where('estado', EstadoCuentaMembresia::ESTADO_ACTIVA)
            ->where('mes_pagado', '<', $mesPagado)
            ->update(['estado' => EstadoCuentaMembresia::ESTADO_EXPIRADA]);
    }

    public function revertirUltimaGeneracion(): array
    {
        return DB::transaction(function () {
            $ultimoMes = EstadoCuentaMembresia::max('mes_pagado');

            if (!$ultimoMes) {
                return [
                    'ok' => false,
                    'mensaje' => 'No hay estados de cuenta para revertir.',
                    'borrados' => 0,
                    'restaurados' => 0,
                    'mes' => null,
                ];
            }

            $registros = EstadoCuentaMembresia::query()
                ->where('mes_pagado', $ultimoMes)
                ->where('observaciones', 'LIKE', 'Generado por %')
                ->whereColumn('created_at', 'updated_at')
                ->get();

            if ($registros->isEmpty()) {
                return [
                    'ok' => false,
                    'mensaje' => "No hay registros revertibles del mes {$ultimoMes}. Los existentes ya fueron modificados manualmente.",
                    'borrados' => 0,
                    'restaurados' => 0,
                    'mes' => $ultimoMes,
                ];
            }

            $usersAfectados = $registros->pluck('user_id')->unique()->all();
            $ids = $registros->pluck('id')->all();

            $borrados = EstadoCuentaMembresia::whereIn('id', $ids)->delete();

            $restaurados = 0;
            foreach ($usersAfectados as $userId) {
                $masReciente = EstadoCuentaMembresia::query()
                    ->where('user_id', $userId)
                    ->where('estado', EstadoCuentaMembresia::ESTADO_EXPIRADA)
                    ->orderByDesc('mes_pagado')
                    ->orderByDesc('id')
                    ->first();

                if ($masReciente) {
                    $masReciente->estado = EstadoCuentaMembresia::ESTADO_ACTIVA;
                    $masReciente->save();
                    $restaurados++;
                }
            }

            return [
                'ok' => true,
                'mensaje' => "Mes {$ultimoMes} revertido: {$borrados} estados borrados, {$restaurados} restaurados a Activa.",
                'borrados' => $borrados,
                'restaurados' => $restaurados,
                'mes' => $ultimoMes,
            ];
        });
    }
}

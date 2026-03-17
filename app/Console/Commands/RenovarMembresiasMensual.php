<?php

namespace App\Console\Commands;

use App\Models\EstadoCuentaMembresia;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RenovarMembresiasMensual extends Command
{
    protected $signature = 'membresias:renovar-mensual {--fecha=}';

    protected $description = 'Genera/renueva la inscripción de membresía para el mes en curso y expira meses anteriores.';

    public function handle(): int
    {
        $now = $this->option('fecha') ? Carbon::parse($this->option('fecha')) : Carbon::now();
        $periodo = $now->format('Y-m');

        $this->info("Procesando periodo: {$periodo}");

        User::whereHas('membresiaUsuario', function ($query) {
                $query->whereNotNull('membresia_id');
            })
            ->with(['membresiaUsuario.membresia'])
            ->chunkById(200, function ($users) use ($periodo, $now) {
                foreach ($users as $user) {
                    $this->procesarUsuario($user, $periodo, $now);
                }
            });

        $this->info('Renovación mensual finalizada');
        return Command::SUCCESS;
    }

    protected function procesarUsuario(User $user, string $periodo, Carbon $fechaProceso): void
    {
        DB::transaction(function () use ($user, $periodo, $fechaProceso) {
            $membresiaProfile = $user->membresiaUsuario;
            $membresiaId = $membresiaProfile?->membresia_id;
            if (!$membresiaId) {
                return;
            }

            $registroActual = EstadoCuentaMembresia::query()
                ->where('user_id', $user->id)
                ->where('membresia_id', $membresiaId)
                ->where('mes_pagado', $periodo)
                ->latest('id')
                ->first();

            $estadoActivo = EstadoCuentaMembresia::query()
                ->where('user_id', $user->id)
                ->where('membresia_id', $membresiaId)
                ->where('estado', EstadoCuentaMembresia::ESTADO_ACTIVA)
                ->orderByDesc('mes_pagado')
                ->orderByDesc('created_at')
                ->first();

            $plantilla = $estadoActivo
                ?: EstadoCuentaMembresia::query()
                    ->where('user_id', $user->id)
                    ->where('membresia_id', $membresiaId)
                    ->orderByDesc('mes_pagado')
                    ->orderByDesc('created_at')
                    ->first();

            if ($registroActual) {
                $modoActual = $registroActual->modo ?: $plantilla?->modo;
                $esSuscripcion = $this->esModoSuscripcion($modoActual);

                $registroActual->estado = EstadoCuentaMembresia::ESTADO_ACTIVA;
                if ($modoActual) {
                    $registroActual->modo = $modoActual;
                }
                if ($esSuscripcion) {
                    $registroActual->pagado = true;
                    $registroActual->fecha_pago = $registroActual->fecha_pago ?: $fechaProceso->toDateString();
                }
                $registroActual->save();

                $this->expirarOtrosActivos($user->id, $registroActual->id);
                return;
            }

            if ($estadoActivo && $estadoActivo->mes_pagado < $periodo) {
                $estadoActivo->estado = EstadoCuentaMembresia::ESTADO_EXPIRADA;
                $estadoActivo->save();
            }

            EstadoCuentaMembresia::where('user_id', $user->id)
                ->where('membresia_id', $membresiaId)
                ->where('mes_pagado', '<', $periodo)
                ->update(['estado' => EstadoCuentaMembresia::ESTADO_EXPIRADA]);

            $importe = (float) ($plantilla?->importe ?? ($membresiaProfile?->membresia?->valor ?? 0));
            $modo = $plantilla?->modo;
            $esSuscripcion = $this->esModoSuscripcion($modo);

            $nuevoEstado = EstadoCuentaMembresia::create([
                'user_id' => $user->id,
                'membresia_id' => $membresiaId,
                'mes_pagado' => $periodo,
                'fecha_pago' => $esSuscripcion ? $fechaProceso->toDateString() : null,
                'importe' => $importe,
                'pagado' => $esSuscripcion,
                'estado' => EstadoCuentaMembresia::ESTADO_ACTIVA,
                'modo' => $modo,
                'info_pago' => $plantilla?->info_pago,
                'observaciones' => $plantilla?->observaciones ?: 'Renovación automática mensual',
            ]);

            $this->expirarOtrosActivos($user->id, $nuevoEstado->id);
        });
    }

    private function expirarOtrosActivos(int $userId, int $exceptId): void
    {
        EstadoCuentaMembresia::query()
            ->where('user_id', $userId)
            ->where('estado', EstadoCuentaMembresia::ESTADO_ACTIVA)
            ->where('id', '!=', $exceptId)
            ->update(['estado' => EstadoCuentaMembresia::ESTADO_EXPIRADA]);
    }

    private function esModoSuscripcion(?string $modo): bool
    {
        $normalizado = mb_strtolower(trim((string) $modo), 'UTF-8');
        $normalizado = strtr($normalizado, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
        ]);

        return $normalizado === 'suscripcion';
    }
}

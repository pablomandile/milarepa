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

        User::whereNotNull('membresia_id')
            ->with(['membresia.esquemaPrecio'])
            ->chunkById(200, function ($users) use ($periodo) {
                foreach ($users as $user) {
                    $this->procesarUsuario($user, $periodo);
                }
            });

        $this->info('Renovación mensual finalizada');
        return Command::SUCCESS;
    }

    protected function procesarUsuario(User $user, string $periodo): void
    {
        DB::transaction(function () use ($user, $periodo) {
            // Expirar meses anteriores a este periodo
            EstadoCuentaMembresia::where('user_id', $user->id)
                ->where('membresia_id', $user->membresia_id)
                ->where('mes_pagado', '<', $periodo)
                ->update(['estado' => EstadoCuentaMembresia::ESTADO_EXPIRADA]);

            // Crear registro activo para el periodo si no existe
            $existe = EstadoCuentaMembresia::where('user_id', $user->id)
                ->where('membresia_id', $user->membresia_id)
                ->where('mes_pagado', $periodo)
                ->exists();

            if ($existe) {
                return;
            }

            $importe = 0;
            if ($user->membresia && $user->membresia->esquemaPrecio) {
                $importe = $user->membresia->esquemaPrecio->precio ?? 0;
            }

            EstadoCuentaMembresia::create([
                'user_id' => $user->id,
                'membresia_id' => $user->membresia_id,
                'mes_pagado' => $periodo,
                'fecha_pago' => null,
                'importe' => $importe,
                'pagado' => false,
                'estado' => EstadoCuentaMembresia::ESTADO_ACTIVA,
                'observaciones' => 'Renovación automática mensual',
            ]);
        });
    }
}

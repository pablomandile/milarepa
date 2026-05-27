<?php

namespace App\Console\Commands;

use App\Services\GeneradorEstadosCuentaMembresiaService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RenovarMembresiasMensual extends Command
{
    protected $signature = 'membresias:renovar-mensual {--fecha=}';

    protected $description = 'Genera estados de cuenta de membresía para el mes indicado (default: mes en curso). Respaldo CLI del botón "Generar" en la UI.';

    public function handle(GeneradorEstadosCuentaMembresiaService $service): int
    {
        $fecha = $this->option('fecha') ? Carbon::parse($this->option('fecha')) : Carbon::now();
        $periodo = $fecha->format('Y-m');

        $this->info("Procesando periodo: {$periodo}");

        $stats = $service->generarParaMes($periodo);

        $this->info("Usuarios procesados: {$stats['usuarios_procesados']}");
        $this->info("Estados creados: {$stats['creados']}");
        $this->info("Estados actualizados (suscripción): {$stats['actualizados']}");
        $this->info("Estados expirados: {$stats['expirados']}");

        return Command::SUCCESS;
    }
}

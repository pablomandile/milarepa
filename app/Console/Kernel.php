<?php

namespace App\Console;

use App\Console\Commands\EnviarReporteSemanalInscripcionesActividad;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\RenovarMembresiasMensual;
use App\Models\ConfiguracionSistema;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Genera/renueva la inscripción de membresía al iniciar cada mes (2 AM)
        $schedule->command(RenovarMembresiasMensual::class)
            ->monthlyOn(1, '02:00')
            ->withoutOverlapping();

        $schedule->command(EnviarReporteSemanalInscripcionesActividad::class)
            ->weeklyOn($this->obtenerDiaSemanaReporte(), $this->obtenerHoraReporte())
            ->when(function () {
                return ConfiguracionSistema::obtenerBoolean('enviar_mail_semanal_inscripciones_actividad', false);
            })
            ->withoutOverlapping();
    }

    private function obtenerDiaSemanaReporte(): int
    {
        $dia = ConfiguracionSistema::obtenerTexto('envio_mail_semanal_inscripciones_dia', 'viernes');

        return match (strtolower(trim($dia))) {
            'lunes' => 1,
            'martes' => 2,
            'miercoles' => 3,
            'jueves' => 4,
            'viernes' => 5,
            'sabado' => 6,
            'domingo' => 0,
            default => 5,
        };
    }

    private function obtenerHoraReporte(): string
    {
        $hora = ConfiguracionSistema::obtenerTexto('envio_mail_semanal_inscripciones_hora', '17:00');
        return preg_match('/^\d{2}:\d{2}$/', $hora) ? $hora : '17:00';
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

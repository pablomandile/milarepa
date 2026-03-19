<?php

namespace App\Console\Commands;

use App\Models\EmailPlantilla;
use App\Models\EmailEnvioConfiguracion;
use App\Models\ConfiguracionSistema;
use App\Models\Entidad;
use App\Models\EnvioMail;
use App\Services\ReporteInscripcionesPorActividadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EnviarReporteSemanalInscripcionesActividad extends Command
{
    protected $signature = 'emails:reporte-semanal-inscripciones-actividad';

    protected $description = 'Envía por mail el reporte semanal de inscripciones por actividad.';

    public function handle(ReporteInscripcionesPorActividadService $reporteService): int
    {
        $entidadPrincipal = Entidad::query()
            ->where('entidad_principal', true)
            ->first();

        $destinatarioConfigurado = trim(ConfiguracionSistema::obtenerTexto('envio_mail_semanal_inscripciones_destinatario', ''));

        if (!empty($destinatarioConfigurado)) {
            $destinatarios = collect([$destinatarioConfigurado]);
        } else {
            $destinatarios = collect([
                $entidadPrincipal?->email1,
                $entidadPrincipal?->email2,
            ])
                ->filter(fn ($email) => !empty($email))
                ->unique()
                ->values();
        }

        if ($destinatarios->isEmpty()) {
            $this->warn('No hay destinatarios configurados en email1/email2 de la Entidad Principal.');
            return self::SUCCESS;
        }

        $configuracionEnvio = EmailEnvioConfiguracion::resolverPlantilla('reporte_semanal_inscripciones_actividad');
        $nombrePlantilla = EmailPlantilla::query()
            ->where('plantilla_archivo', $configuracionEnvio['archivo'])
            ->value('nombre') ?: $configuracionEnvio['nombre'];
        $reporte = $reporteService->construirReporte();

        $enviados = 0;
        $fallidos = 0;

        foreach ($destinatarios as $email) {
            try {
                Mail::send($configuracionEnvio['view'], [
                    'resumen' => $reporte['resumen'],
                    'actividades' => $reporte['actividades'],
                    'rango' => $reporte['rango'],
                    'entidadPrincipal' => $entidadPrincipal,
                ], function ($message) use ($email) {
                    $message->to($email)
                        ->subject('Reporte semanal de inscripciones por actividad');
                });

                EnvioMail::create([
                    'fecha' => now()->toDateString(),
                    'tipo' => 'Automático',
                    'user_id' => null,
                    'destinatario' => $email,
                    'motivo' => $nombrePlantilla,
                ]);

                $enviados++;
            } catch (Throwable $exception) {
                $fallidos++;
                $this->warn("No se pudo enviar a {$email}: {$exception->getMessage()}");
            }
        }

        $this->info("Reporte semanal enviado. Exitosos: {$enviados}. Fallidos: {$fallidos}.");
        return self::SUCCESS;
    }
}

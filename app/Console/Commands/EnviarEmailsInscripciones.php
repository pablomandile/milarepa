<?php

namespace App\Console\Commands;

use App\Models\Inscripcion;
use App\Services\EmailInscripcionService;
use Illuminate\Console\Command;

class EnviarEmailsInscripciones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:inscripciones {--inscripcion_id=} {--user_id=} {--actividad_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enviar emails de confirmación de inscripción. Puede usarse para reenviar emails.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $inscripcionId = $this->option('inscripcion_id');
        $userId = $this->option('user_id');
        $actividadId = $this->option('actividad_id');

        $query = Inscripcion::query();

        // Filtros opcionales
        if ($inscripcionId) {
            $query->where('id', $inscripcionId);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($actividadId) {
            $query->where('actividad_id', $actividadId);
        }

        $inscripciones = $query->get();

        if ($inscripciones->isEmpty()) {
            $this->error('No se encontraron inscripciones que coincidan con los criterios.');
            return self::FAILURE;
        }

        $this->info("Enviando emails a {$inscripciones->count()} inscripción(es)...");

        $resultados = EmailInscripcionService::enviarMasivo($inscripciones->toArray());

        $this->info("\n✓ Emails enviados: {$resultados['exitosas']}");
        
        if ($resultados['fallidas'] > 0) {
            $this->warn("✗ Emails fallidos: {$resultados['fallidas']}");
            
            if (!empty($resultados['errores'])) {
                $this->warn("\nErrores en:");
                foreach ($resultados['errores'] as $error) {
                    $this->warn("  - {$error}");
                }
            }
        }

        return self::SUCCESS;
    }
}

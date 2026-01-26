<?php

namespace App\Console\Commands;

use App\Models\Inscripcion;
use App\Mail\InscripcionConfirmada;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class DebugEmailInscripcion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:email {inscripcion_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar envío de email de inscripción y mostrar detalles de la respuesta';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $inscripcionId = $this->argument('inscripcion_id');

        $inscripcion = Inscripcion::with([
            'actividad.entidad',
            'actividad.descripcion',
            'actividad.modalidad',
            'user',
            'estado'
        ])->find($inscripcionId);

        if (!$inscripcion) {
            $this->error("Inscripción #{$inscripcionId} no encontrada.");
            return self::FAILURE;
        }

        $this->info("📧 Probando envío de email para inscripción #{$inscripcionId}");
        $this->line("Usuario: {$inscripcion->user->name} ({$inscripcion->user->email})");
        $this->line("Actividad: {$inscripcion->actividad->nombre}");
        $this->newLine();

        // Mostrar configuración de mail
        $this->info("⚙️  Configuración de Mail:");
        $this->line("  • MAILER: " . config('mail.driver'));
        $this->line("  • HOST: " . config('mail.host'));
        $this->line("  • PORT: " . config('mail.port'));
        $this->line("  • USERNAME: " . config('mail.username'));
        $this->line("  • ENCRYPTION: " . config('mail.encryption'));
        $this->line("  • FROM: " . config('mail.from.address'));
        $this->newLine();

        try {
            $this->info("Intentando enviar email...");
            
            Mail::to($inscripcion->user->email)->send(new InscripcionConfirmada($inscripcion));
            
            $this->info("✅ Email enviado correctamente!");
            $this->line("Destinatario: {$inscripcion->user->email}");
            
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Error al enviar email:");
            $this->line("Tipo de error: " . get_class($e));
            $this->line("Mensaje: " . $e->getMessage());
            $this->line("Código: " . $e->getCode());
            
            // Mostrar traza si está en debug
            if (config('app.debug')) {
                $this->newLine();
                $this->info("📋 Stack trace:");
                $this->line($e->getTraceAsString());
            }
            
            return self::FAILURE;
        }
    }
}

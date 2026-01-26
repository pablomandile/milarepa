<?php

namespace App\Services;

use App\Models\Inscripcion;
use App\Mail\InscripcionConfirmada;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailInscripcionService
{
    /**
     * Enviar email de confirmación de inscripción
     * 
     * @param Inscripcion $inscripcion
     * @return bool
     */
    public static function enviarConfirmacion(Inscripcion $inscripcion): bool
    {
        try {
            // Cargar relaciones si no están cargadas
            if (!$inscripcion->relationLoaded('actividad')) {
                $inscripcion->load([
                    'actividad.entidad',
                    'actividad.descripcion',
                    'actividad.modalidad',
                    'user',
                    'estado'
                ]);
            }

            // Enviar email
            Mail::to($inscripcion->user->email)
                ->send(new InscripcionConfirmada($inscripcion));

            Log::info('Email de inscripción enviado correctamente', [
                'inscripcion_id' => $inscripcion->id,
                'user_email' => $inscripcion->user->email
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Error al enviar email de inscripción', [
                'inscripcion_id' => $inscripcion->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Enviar email a múltiples inscripciones
     * 
     * @param array $inscripciones
     * @return array
     */
    public static function enviarMasivo(array $inscripciones): array
    {
        $resultados = [
            'exitosas' => 0,
            'fallidas' => 0,
            'errores' => []
        ];

        foreach ($inscripciones as $inscripcion) {
            if (self::enviarConfirmacion($inscripcion)) {
                $resultados['exitosas']++;
            } else {
                $resultados['fallidas']++;
                $resultados['errores'][] = "Inscripción #{$inscripcion->id}";
            }
        }

        return $resultados;
    }
}

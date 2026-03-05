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
                    'actividad.imagen',
                    'actividad.descripcion',
                    'actividad.modalidad',
                    'actividad.stream.links',
                    'user',
                ]);
            }

            // Enviar email
            Mail::to($inscripcion->user->email)
                ->send(new InscripcionConfirmada($inscripcion));
            $inscripcion->envioRegistro = 'Enviada';
            if ($inscripcion->estado === 'Confirmada') {
                $inscripcion->envioConfirmacion = 'Enviada';
            }
            $inscripcion->save();

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
     * Enviar plantilla de confirmacion (estado de inscripciones masivo).
     */
    public static function enviarPlantillaConfirmacion(Inscripcion $inscripcion): bool
    {
        try {
            $inscripcion->loadMissing([
                'actividad.entidad',
                'actividad.imagen',
                'actividad.descripcion',
                'actividad.modalidad',
                'actividad.stream.links',
                'user',
                'guestUser',
            ]);

            $destinatario = self::resolverDestinatario($inscripcion);
            if (!$destinatario) {
                return false;
            }

            Mail::to($destinatario)->send(
                new InscripcionConfirmada($inscripcion, 'emails.inscripcion_confirmada')
            );

            $inscripcion->envioRegistro = 'Enviada';
            $inscripcion->envioConfirmacion = 'Enviada';
            $inscripcion->save();

            return true;
        } catch (\Throwable $e) {
            Log::error('Error al enviar plantilla de confirmacion', [
                'inscripcion_id' => $inscripcion->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Enviar plantilla de grabacion disponible.
     */
    public static function enviarPlantillaGrabacion(Inscripcion $inscripcion): bool
    {
        try {
            $inscripcion->loadMissing([
                'actividad.entidad',
                'actividad.imagen',
                'actividad.descripcion',
                'actividad.modalidad',
                'actividad.grabacion.linksgrabacion',
                'user',
                'guestUser',
            ]);

            $destinatario = self::resolverDestinatario($inscripcion);
            if (!$destinatario) {
                return false;
            }

            Mail::to($destinatario)->send(
                new InscripcionConfirmada($inscripcion, 'emails.envio_grabacion')
            );

            $inscripcion->envioGrabacion = 'Enviada';
            $inscripcion->save();

            return true;
        } catch (\Throwable $e) {
            Log::error('Error al enviar plantilla de grabacion', [
                'inscripcion_id' => $inscripcion->id,
                'error' => $e->getMessage(),
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

    private static function resolverDestinatario(Inscripcion $inscripcion): ?string
    {
        $email = $inscripcion->guestUser?->email ?: $inscripcion->user?->email;
        return $email ? (string) $email : null;
    }
}

<?php

namespace App\Services;

use App\Mail\InscripcionConfirmada;
use App\Models\EmailEnvioConfiguracion;
use App\Models\EnvioMail;
use App\Models\Inscripcion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envío del mail de registro/confirmación de una inscripción.
 *
 * Centraliza la lógica que estaba inline en GridActividadesController::finalizarPago
 * para poder reutilizarla desde el webhook de Mercado Pago al confirmar un pago.
 */
class InscripcionMailService
{
    /**
     * Envía el mail correspondiente según el estado de pago:
     * - 'Saldado'  → plantilla "inscripcion_confirmada"
     * - otro       → plantilla "inscripcion_registrada"
     *
     * Actualiza los flags de envío y registra el EnvioMail. No relanza errores
     * de mail (los loguea) para no romper el flujo de pago.
     */
    public function enviarConfirmacionRegistro(Inscripcion $inscripcion): void
    {
        $destinatario = $inscripcion->guestUser?->email ?: $inscripcion->user?->email;
        if (empty($destinatario)) {
            return;
        }

        try {
            $proceso = $inscripcion->pago === 'Saldado'
                ? 'inscripcion_confirmada'
                : 'inscripcion_registrada';
            $configuracion = EmailEnvioConfiguracion::resolverPlantilla($proceso);

            Mail::to($destinatario)->send(
                new InscripcionConfirmada($inscripcion, $configuracion['view'])
            );

            $inscripcion->envioRegistro = 'Enviada';
            if ($inscripcion->estado === 'Confirmada') {
                $inscripcion->envioConfirmacion = 'Enviada';
            }
            $inscripcion->save();

            EnvioMail::create([
                'fecha' => now()->toDateString(),
                'tipo' => 'Automático',
                'user_id' => null,
                'destinatario' => $destinatario,
                'motivo' => $configuracion['nombre'],
            ]);
        } catch (\Exception $e) {
            Log::error('Error al enviar mail de inscripcion', [
                'inscripcion_id' => $inscripcion->id,
                'destinatario' => $destinatario,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

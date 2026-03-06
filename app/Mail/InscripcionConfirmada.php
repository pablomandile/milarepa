<?php

namespace App\Mail;

use App\Models\Inscripcion;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InscripcionConfirmada extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Inscripcion $inscripcion,
        public ?string $plantilla = null
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $plantilla = $this->resolverPlantilla();
        $subject = match ($plantilla) {
            'emails.inscripcion_registrada' => 'Inscripcion registrada',
            'emails.envio_grabacion' => 'Grabacion disponible',
            'emails.informacion_membresias' => 'Informacion solicitada sobre Tarjetas Kadampa',
            default => 'Inscripcion confirmada',
        };

        return new Envelope(
            subject: $subject . ' - ' . $this->inscripcion->actividad->nombre,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: $this->resolverPlantilla(),
            with: [
                'inscripcion' => $this->inscripcion,
                'actividad' => $this->inscripcion->actividad,
                'usuario' => $this->inscripcion->guestUser ?: $this->inscripcion->user,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    private function resolverPlantilla(): string
    {
        if (in_array($this->plantilla, [
            'emails.inscripcion_confirmada',
            'emails.inscripcion_registrada',
            'emails.envio_grabacion',
            'emails.informacion_membresias',
        ], true)) {
            return $this->plantilla;
        }

        return $this->inscripcion->estado === 'Confirmada'
            ? 'emails.inscripcion_confirmada'
            : 'emails.inscripcion_registrada';
    }
}

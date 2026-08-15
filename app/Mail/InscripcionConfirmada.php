<?php

namespace App\Mail;

use App\Models\Inscripcion;
use App\Models\Entidad;
use App\Models\Moneda;
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
            'emails.inscripcion_tk_registrada' => 'Inscripcion Tarjeta Kadampa registrada',
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
        $this->inscripcion->loadMissing([
            'user.membresia.botonPago.metodoPago.imagen',
            'actividad.metodosPago.imagen',
            'moneda',
            'invitados',
            'invitados.comidas',
            'invitados.transportes',
            'invitados.hospedajes',
        ]);

        // Multi-moneda (BUSINESS_RULES §2.1bis): los montos de la inscripción están
        // en su moneda, y `monto_moneda_principal` es la porción de servicios que no
        // tenían precio en esa moneda y se cobran en pesos. Sin moneda (legacy) todo
        // es principal y el mail se ve igual que siempre.
        $monedaPrincipal = Moneda::principal();
        $moneda = $this->inscripcion->moneda ?: $monedaPrincipal;

        return new Content(
            view: $this->resolverPlantilla(),
            with: [
                'inscripcion' => $this->inscripcion,
                'actividad' => $this->inscripcion->actividad,
                'usuario' => $this->inscripcion->guestUser ?: $this->inscripcion->user,
                'entidadPrincipal' => Entidad::where('entidad_principal', true)->first(),
                'simboloMoneda' => $moneda?->simbolo ?: '$',
                'simboloPrincipal' => $monedaPrincipal?->simbolo ?: '$',
                'montoMonedaPrincipal' => (float) ($this->inscripcion->monto_moneda_principal ?? 0),
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
        if (is_string($this->plantilla) && str_starts_with($this->plantilla, 'emails.')) {
            return $this->plantilla;
        }

        return $this->inscripcion->estado === 'Confirmada'
            ? 'emails.inscripcion_confirmada'
            : 'emails.inscripcion_registrada';
    }
}

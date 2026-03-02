<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmailPreviewController extends Controller
{
    /**
     * Landing de preview de emails
     */
    public function landing()
    {
        return Inertia::render('EmailPreview/Landing');
    }

    /**
     * Vista previa del email de inscripción registrada
     */
    public function inscripcionRegistrada($id = null)
    {
        $inscripcion = $this->obtenerInscripcionParaPreview($id);

        return response()->view('emails.inscripcion_registrada', [
            'inscripcion' => $inscripcion,
            'actividad' => $inscripcion->actividad,
            'usuario' => $inscripcion->user,
        ], 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * Vista previa del email de inscripción confirmada
     */
    public function inscripcionConfirmada($id = null)
    {
        $inscripcion = $this->obtenerInscripcionParaPreview($id);
        $inscripcion->estado = 'Confirmada';
        $inscripcion->pago = 'Saldado';

        return response()->view('emails.inscripcion_confirmada', [
            'inscripcion' => $inscripcion,
            'actividad' => $inscripcion->actividad,
            'usuario' => $inscripcion->user,
        ], 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    private function obtenerInscripcionParaPreview($id = null)
    {
        if ($id) {
            $inscripcion = Inscripcion::with([
                'actividad.entidad',
                'actividad.imagen',
                'actividad.descripcion',
                'actividad.modalidad',
                'actividad.stream.links',
                'user',
                'hospedaje',
                'comida',
                'transporte'
            ])->find($id);

            if (!$inscripcion) {
                abort(404, 'Inscripción no encontrada');
            }

            return $inscripcion;
        }

        return $this->crearInscripcionPrueba();
    }

    /**
     * Crear una inscripción de prueba con datos falsos
     */
    private function crearInscripcionPrueba()
    {
        // Simular objetos con datos de prueba
        $usuario = (object) [
            'name' => 'Juan Carlos Pérez',
            'email' => 'juan@example.com',
        ];

        $entidad = (object) [
            'nombre' => 'Centro de Yoga y Meditación Milarepa',
        ];

        $descripcion = (object) [
            'descripcion' => 'Aprenderás técnicas ancestrales de meditación tibetana, incluyendo respiración consciente, visualización y mantras. Este retiro es perfecto para principiantes y practicantes avanzados.',
        ];

        $modalidad = (object) [
            'nombre' => 'Presencial',
        ];

        $hospedaje = (object) [
            'nombre' => 'Habitación compartida',
        ];

        $comida = (object) [
            'nombre' => 'Pensión completa',
        ];

        $transporte = (object) [
            'nombre' => 'Transporte incluido desde Buenos Aires',
        ];

        $stream = (object) [
            'nombre' => 'Streaming principal',
            'links' => [
                (object) [
                    'nombre' => 'YouTube Live',
                    'link' => 'https://www.youtube.com/watch?v=demo123',
                ],
                (object) [
                    'nombre' => 'Zoom',
                    'link' => 'https://zoom.us/j/1234567890',
                ],
            ],
        ];

        $actividad = (object) [
            'nombre' => 'Retiro de Meditación - Fin de Semana',
            'fecha_inicio' => now()->addDays(10)->setHour(14)->setMinute(30),
            'entidad' => $entidad,
            'descripcion' => $descripcion,
            'modalidad' => $modalidad,
            'stream' => $stream,
        ];

        $inscripcion = (object) [
            'id' => 999,
            'online' => false,
            'membresia' => 'Miembro Activo',
            'precioGeneral' => 5000.00,
            'montoapagar' => 3500.00,
            'montoGrabacion' => null,
            'pago' => 'Parcial',
            'estado' => 'Registrada',
            'actividad' => $actividad,
            'user' => $usuario,
            'hospedaje' => $hospedaje,
            'comida' => $comida,
            'transporte' => $transporte,
        ];

        return $inscripcion;
    }
}


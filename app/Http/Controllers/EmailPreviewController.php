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
     * Vista previa del email de inscripción confirmada
     */
    public function inscripcionConfirmada($id = null)
    {
        if ($id) {
            // Cargar una inscripción real
            $inscripcion = Inscripcion::with([
                'actividad.entidad',
                'actividad.descripcion',
                'actividad.modalidad',
                'user',
                'estado',
                'hospedaje',
                'comida',
                'transporte'
            ])->find($id);

            if (!$inscripcion) {
                abort(404, 'Inscripción no encontrada');
            }
        } else {
            // Crear datos de prueba
            $inscripcion = $this->crearInscripcionPrueba();
        }

        // Renderizar la vista del email directamente (sin Inertia)
        return response()->view('emails.inscripcion_confirmada', [
            'inscripcion' => $inscripcion,
            'actividad' => $inscripcion->actividad,
            'usuario' => $inscripcion->user,
        ], 200, ['Content-Type' => 'text/html; charset=UTF-8']);
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

        $estado = (object) [
            'nombre' => 'Confirmada',
        ];

        $actividad = (object) [
            'nombre' => 'Retiro de Meditación - Fin de Semana',
            'fecha_inicio' => now()->addDays(10)->setHour(14)->setMinute(30),
            'entidad' => $entidad,
            'descripcion' => $descripcion,
            'modalidad' => $modalidad,
        ];

        $inscripcion = (object) [
            'id' => 999,
            'membresia' => 'Miembro Activo',
            'precioGeneral' => 5000.00,
            'montoapagar' => 3500.00,
            'pago' => 'Parcial',
            'actividad' => $actividad,
            'user' => $usuario,
            'estado' => $estado,
            'hospedaje' => $hospedaje,
            'comida' => $comida,
            'transporte' => $transporte,
        ];

        return $inscripcion;
    }
}


<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
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
     * Vista previa del email de inscripcion registrada
     */
    public function inscripcionRegistrada($id = null)
    {
        $inscripcion = $this->obtenerInscripcionParaPreview($id);
        $esPreviewPrueba = is_null($id);

        return response()->view('emails.inscripcion_registrada', [
            'inscripcion' => $inscripcion,
            'actividad' => $inscripcion->actividad,
            'usuario' => $inscripcion->user,
            'esPreviewPrueba' => $esPreviewPrueba,
        ], 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * Vista previa del email de inscripcion confirmada
     */
    public function inscripcionConfirmada($id = null)
    {
        $inscripcion = $this->obtenerInscripcionParaPreview($id);
        $inscripcion->estado = 'Confirmada';
        $inscripcion->pago = 'Saldado';
        $esPreviewPrueba = is_null($id);

        return response()->view('emails.inscripcion_confirmada', [
            'inscripcion' => $inscripcion,
            'actividad' => $inscripcion->actividad,
            'usuario' => $inscripcion->user,
            'esPreviewPrueba' => $esPreviewPrueba,
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
                'actividad.metodosPago',
                'actividad.botonPago',
                'actividad.grabacion.botonPago',
                'user',
                'hospedaje.botonPago',
                'comida.botonPago',
                'transporte.botonPago',
            ])->find($id);

            if (!$inscripcion) {
                abort(404, 'Inscripcion no encontrada');
            }

            return $inscripcion;
        }

        return $this->crearInscripcionPrueba();
    }

    /**
     * Crear una inscripcion de prueba con datos falsos
     */
    private function crearInscripcionPrueba()
    {
        $botonActividad = (object) [
            'nombre' => 'Getnet Actividad',
            'link' => 'https://www.getnet.com.ar/pago/actividad-demo',
        ];
        $botonGrabacion = (object) [
            'nombre' => 'Getnet Grabacion',
            'link' => 'https://www.getnet.com.ar/pago/grabacion-demo',
        ];
        $botonHospedaje = (object) [
            'nombre' => 'Getnet Hospedaje',
            'link' => 'https://www.getnet.com.ar/pago/hospedaje-demo',
        ];
        $botonComida = (object) [
            'nombre' => 'Getnet Comida',
            'link' => 'https://www.getnet.com.ar/pago/comida-demo',
        ];
        $botonTransporte = (object) [
            'nombre' => 'Getnet Transporte',
            'link' => 'https://www.getnet.com.ar/pago/transporte-demo',
        ];

        $usuario = (object) [
            'name' => 'Juan Carlos Perez',
            'email' => 'juan@example.com',
            'membresia_id' => 2,
        ];

        $entidad = (object) [
            'nombre' => 'Centro de Yoga y Meditacion Milarepa',
        ];

        $descripcion = (object) [
            'descripcion' => 'Aprenderas tecnicas ancestrales de meditacion tibetana, incluyendo respiracion consciente, visualizacion y mantras.',
        ];

        $modalidad = (object) [
            'nombre' => 'Presencial',
        ];

        $hospedaje = (object) [
            'nombre' => 'Habitacion compartida',
            'botonPago' => $botonHospedaje,
        ];

        $comida = (object) [
            'nombre' => 'Pension completa',
            'botonPago' => $botonComida,
        ];

        $transporte = (object) [
            'nombre' => 'Transporte incluido desde Buenos Aires',
            'descripcion' => 'Transporte incluido desde Buenos Aires',
            'botonPago' => $botonTransporte,
        ];

        $grabacion = (object) [
            'nombre' => 'Grabacion HD',
            'botonPago' => $botonGrabacion,
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

        $metodosPago = [
            (object) ['id' => 1, 'nombre' => 'Efectivo', 'descripcion' => null],
            (object) ['id' => 2, 'nombre' => 'Tarjeta de Credito', 'descripcion' => null],
            (object) ['id' => 3, 'nombre' => 'Tarjeta de Debito', 'descripcion' => null],
            (object) [
                'id' => 4,
                'nombre' => 'Transferencia',
                'descripcion' => 'CBU: 0000003100012345678901 - Alias: MILAREPA.CENTRO. Adjunta el comprobante con tu nombre y apellido.',
            ],
            (object) ['id' => 5, 'nombre' => 'Getnet', 'descripcion' => null],
        ];

        $lineaGeneral = (object) [
            'membresia_id' => 1,
            'precio' => 5000.00,
            'membresia' => (object) ['nombre' => 'Sin membresia'],
            'botonPago' => $botonActividad,
        ];
        $lineaMembresia = (object) [
            'membresia_id' => 2,
            'precio' => 3500.00,
            'membresia' => (object) ['nombre' => 'Miembro Activo'],
            'botonPago' => $botonActividad,
        ];

        $actividad = new class {
            public function loadMissing(array $relations = [])
            {
                return $this;
            }
        };

        $actividad->nombre = 'Retiro de Meditacion - Fin de Semana';
        $actividad->fecha_inicio = now()->addDays(10)->setHour(14)->setMinute(30);
        $actividad->entidad = $entidad;
        $actividad->descripcion = $descripcion;
        $actividad->modalidad = $modalidad;
        $actividad->stream = $stream;
        $actividad->botonPago = $botonActividad;
        $actividad->grabacion = $grabacion;
        $actividad->metodosPago = $metodosPago;
        $actividad->esquemaPrecio = (object) ['membresias' => [$lineaGeneral, $lineaMembresia]];
        $actividad->esquemaDescuento = (object) ['membresias' => [$lineaGeneral, $lineaMembresia]];

        $inscripcion = new class {
            public function loadMissing(array $relations = [])
            {
                return $this;
            }
        };

        $inscripcion->id = 999;
        $inscripcion->online = false;
        $inscripcion->membresia = 'Miembro Activo';
        $inscripcion->precioGeneral = 5000.00;
        $inscripcion->montoActividad = 3500.00;
        $inscripcion->montoGrabacion = 650.00;
        $inscripcion->montoHospedaje = 1200.00;
        $inscripcion->montoComidas = 800.00;
        $inscripcion->montoTransporte = 450.00;
        $inscripcion->montoapagar = 6600.00;
        $inscripcion->pago = 'Parcial';
        $inscripcion->estado = 'Registrada';
        $inscripcion->actividad = $actividad;
        $inscripcion->user = $usuario;
        $inscripcion->hospedaje = $hospedaje;
        $inscripcion->comida = $comida;
        $inscripcion->transporte = $transporte;

        return $inscripcion;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Asistencia;
use App\Models\Inscripcion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class AsistenciasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $asistencias = Asistencia::with([
            'inscripcion:id,actividad_id,user_id,estado',
            'inscripcion.actividad:id,nombre',
            'inscripcionClase:id,clase_id,user_id,membresia,pago',
            'inscripcionClase.clase:id,nombre',
            'inscripcionClase.usuario:id,name,email',
            'usuario:id,name,email',
        ])->latest('id')->get();

        return inertia('Asistencias/Index', [
            'asistencias' => $asistencias,
        ]);
    }

    public function registrarDesdeQr(Request $request)
    {
        $validated = $request->validate([
            'qr_content' => ['required', 'string'],
        ]);

        $qrContent = trim((string) $validated['qr_content']);
        $parts = parse_url($qrContent);

        if (!$parts || empty($parts['path'])) {
            return response()->json([
                'message' => 'El contenido del QR no es válido.',
            ], 422);
        }

        $path = (string) $parts['path'];
        $query = isset($parts['query']) ? ('?' . $parts['query']) : '';
        $urlParaValidar = url($path . $query);
        $signedRequest = Request::create($urlParaValidar, 'GET');

        if (!URL::hasValidSignature($signedRequest)) {
            return response()->json([
                'message' => 'El QR es inválido o está vencido.',
            ], 422);
        }

        if (!preg_match('#/inscripciones/(\d+)/asistir$#', $path, $matches)) {
            return response()->json([
                'message' => 'El QR no corresponde a un ticket de inscripción.',
            ], 422);
        }

        $inscripcionId = (int) $matches[1];
        $inscripcion = Inscripcion::find($inscripcionId);

        if (!$inscripcion) {
            return response()->json([
                'message' => 'La inscripción asociada al QR no existe.',
            ], 404);
        }

        $asistencia = Asistencia::updateOrCreate(
            ['inscripcion_id' => $inscripcion->id],
            [
                'usuario_id' => $inscripcion->user_id,
                'asistencia' => 'Presente',
            ]
        );

        if ($inscripcion->asistencia !== 'presente') {
            $inscripcion->asistencia = 'presente';
            $inscripcion->save();
        }

        return response()->json([
            'message' => $asistencia->wasRecentlyCreated
                ? 'Asistencia registrada correctamente.'
                : 'La asistencia ya estaba registrada y fue actualizada.',
        ]);
    }
}

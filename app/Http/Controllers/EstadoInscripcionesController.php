<?php

namespace App\Http\Controllers;

use App\Models\EmailEnvioConfiguracion;
use App\Models\EnvioMail;
use App\Models\Inscripcion;
use App\Services\EmailInscripcionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EstadoInscripcionesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $inscripciones = Inscripcion::with([
                'actividad',
                'actividad.entidad',
                'user',
                'user.pais',
                'user.provincia',
                'user.municipio',
                'user.barrio',
                'guestUser',
                'guestUser.pais',
                'guestUser.provincia',
                'guestUser.municipio',
                'guestUser.barrio',
                'auditorUser',
            'comprobantes',
            ])
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('EstadoInscripciones/Index', [
            'inscripciones' => $inscripciones,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = $request->user();
        if (!$user || !$user->hasRole(['Admin', 'Editor', 'admin', 'editor'])) {
            abort(403);
        }

        $data = $request->validate([
            'montoapagar' => ['required', 'numeric', 'min:0'],
            'pago' => ['required', 'in:Saldado,Parcial,Pendiente'],
        ]);

        $inscripcion = Inscripcion::findOrFail($id);
        $inscripcion->fill($data);
        $inscripcion->auditoria_fecha = now();
        $inscripcion->auditor = $user->id;
        $inscripcion->save();

        return response()->json(['ok' => true]);
    }

    public function countConfirmacionesPendientes(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->hasRole(['Admin', 'Editor', 'admin', 'editor'])) {
            abort(403);
        }

        $total = $this->queryConfirmacionesPendientes()->count();

        return response()->json([
            'ok' => true,
            'total' => $total,
        ]);
    }

    public function enviarConfirmacionesPendientes(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->hasRole(['Admin', 'Editor', 'admin', 'editor'])) {
            abort(403);
        }

        $configuracionConfirmacion = EmailEnvioConfiguracion::resolverPlantilla('inscripcion_confirmada');

        $query = $this->queryConfirmacionesPendientes()->with([
            'actividad.entidad',
            'actividad.imagen',
            'actividad.descripcion',
            'actividad.modalidad',
            'actividad.stream.links',
            'user',
            'guestUser',
        ]);

        $enviadas = 0;
        $errores = 0;
        $sinDestino = 0;

        $query->chunkById(100, function ($inscripciones) use (&$enviadas, &$errores, &$sinDestino, $user, $configuracionConfirmacion) {
            foreach ($inscripciones as $inscripcion) {
                $destinatario = $inscripcion->guestUser?->email ?: $inscripcion->user?->email;
                if (empty($destinatario)) {
                    $sinDestino++;
                    continue;
                }

                if (EmailInscripcionService::enviarPlantillaConfirmacion($inscripcion)) {
                    $this->registrarEnvioManual($destinatario, $user->id, $configuracionConfirmacion['nombre']);
                    $enviadas++;
                } else {
                    $errores++;
                }
            }
        }, 'id');

        return response()->json([
            'ok' => true,
            'enviadas' => $enviadas,
            'errores' => $errores,
            'sin_destino' => $sinDestino,
        ]);
    }

    public function countGrabacionesPendientes(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->hasRole(['Admin', 'Editor', 'admin', 'editor'])) {
            abort(403);
        }

        $total = $this->queryGrabacionesPendientes()->count();

        return response()->json([
            'ok' => true,
            'total' => $total,
        ]);
    }

    public function enviarGrabacionesPendientes(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->hasRole(['Admin', 'Editor', 'admin', 'editor'])) {
            abort(403);
        }

        $configuracionGrabacion = EmailEnvioConfiguracion::resolverPlantilla('envio_grabacion');

        $query = $this->queryGrabacionesPendientes()->with([
            'actividad.entidad',
            'actividad.imagen',
            'actividad.descripcion',
            'actividad.modalidad',
            'actividad.grabacion.linksgrabacion',
            'user',
            'guestUser',
        ]);

        $enviadas = 0;
        $errores = 0;
        $sinDestino = 0;

        $query->chunkById(100, function ($inscripciones) use (&$enviadas, &$errores, &$sinDestino, $user, $configuracionGrabacion) {
            foreach ($inscripciones as $inscripcion) {
                $destinatario = $inscripcion->guestUser?->email ?: $inscripcion->user?->email;
                if (empty($destinatario)) {
                    $sinDestino++;
                    continue;
                }

                if (EmailInscripcionService::enviarPlantillaGrabacion($inscripcion)) {
                    $this->registrarEnvioManual($destinatario, $user->id, $configuracionGrabacion['nombre']);
                    $enviadas++;
                } else {
                    $errores++;
                }
            }
        }, 'id');

        return response()->json([
            'ok' => true,
            'enviadas' => $enviadas,
            'errores' => $errores,
            'sin_destino' => $sinDestino,
        ]);
    }

    private function queryConfirmacionesPendientes()
    {
        return Inscripcion::query()
            ->where('montoapagar', 0)
            ->where('pago', 'Saldado')
            ->where('envioConfirmacion', 'Pendiente')
            ->whereHas('actividad', function ($actividadQuery) {
                $actividadQuery->where(function ($inner) {
                    $inner->whereNull('stream_id')
                        ->orWhereHas('stream.links');
                });
            });
    }

    private function queryGrabacionesPendientes()
    {
        return Inscripcion::query()
            ->where('pago', 'Saldado')
            ->where('envioConfirmacion', 'Enviada')
            ->where('envioGrabacion', 'Pendiente')
            ->whereHas('actividad.grabacion.linksgrabacion');
    }

    private function registrarEnvioManual(string $destinatario, int $userId, string $motivo): void
    {
        EnvioMail::create([
            'fecha' => now()->toDateString(),
            'tipo' => 'Manual',
            'user_id' => $userId,
            'destinatario' => $destinatario,
            'motivo' => $motivo,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\Barrio;
use App\Models\EmailEnvioConfiguracion;
use App\Models\EnvioMail;
use App\Models\Inscripcion;
use App\Models\MetodoPago;
use App\Models\Municipio;
use App\Models\Pais;
use App\Models\Provincia;
use App\Models\User;
use App\Services\CobroService;
use App\Services\EmailInscripcionService;
use App\Services\HospedajeCupoService;
use App\Services\InscripcionServiciosService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
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
            'comprobantes.imagen',
            'cobros',
            'cobros.metodoPago',
            'cobros.moneda',
            'cobros.comprobantes.imagen',
            'cobros.registrador:id,name',
            'invitados',
            'invitados.comidas',
            'invitados.transportes',
            'invitados.hospedajes',
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('EstadoInscripciones/Index', [
            'inscripciones' => $inscripciones,
            // Para el dialog "Crear inscripción" (admin inscribe en nombre de otra persona):
            // sólo actividades activas y que aún no finalizaron (fecha_fin >= ahora).
            'actividades' => Actividad::where('estado', true)
                ->where('fecha_fin', '>=', now())
                ->orderBy('fecha_inicio')
                ->get(['id', 'nombre', 'fecha_inicio', 'fecha_fin']),
            'paises' => Pais::all(),
            'provincias' => Provincia::orderByRaw('FIELD(id, 24) DESC, id ASC')->get(),
            'municipios' => Municipio::all(),
            'barrios' => Barrio::all(),
            'metodosPago' => MetodoPago::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    /**
     * Prepara la sesión de pago (grid_pago) para que el admin inscriba a otra persona
     * y luego complete servicios/invitados/pago en la pantalla de pago existente.
     * (POST, sólo admin/editor.)
     */
    public function crearInscripcionPrepare(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->hasRole(['Admin', 'Editor', 'admin', 'editor'])) {
            abort(403);
        }

        $data = $request->validate([
            'actividad_id' => ['required', 'exists:actividades,id'],
            'modo' => ['required', 'in:nuevo,existente'],
            'user_id' => ['required_if:modo,existente', 'nullable', 'exists:users,id'],
        ] + GridActividadesController::reglasGuest('guest', 'required_if:modo,nuevo'));

        $guest = $data['guest'] ?? null;
        if ($data['modo'] === 'nuevo') {
            $registrarDatos = (bool) ($guest['registrar_datos'] ?? false);
            if ($registrarDatos && !empty($guest['email']) && User::where('email', $guest['email'])->exists()) {
                throw ValidationException::withMessages([
                    'guest.email' => ['Este correo electrónico ya está registrado. Elegí "Participante existente".'],
                ]);
            }
        }

        $request->session()->put('grid_pago', [
            'actividad_id' => $data['actividad_id'],
            'user_id' => $data['modo'] === 'existente' ? (int) $data['user_id'] : null,
            'guest' => $data['modo'] === 'nuevo' ? $guest : null,
            'comprobante_path' => null,
            'pago_metodo' => null,
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Autocomplete de usuarios para el dialog "Crear inscripción". (GET, sólo admin/editor.)
     */
    public function buscarUsuarios(Request $request)
    {
        $user = $request->user();
        if (!$user || !$user->hasRole(['Admin', 'Editor', 'admin', 'editor'])) {
            abort(403);
        }

        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['usuarios' => []]);
        }

        $usuarios = User::query()
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email']);

        return response()->json(['usuarios' => $usuarios]);
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
     * Datos para el dialog de edición admin: servicios de la actividad + selección
     * actual del titular + invitados. (GET, sólo admin/editor.)
     */
    public function editarData(Request $request, string $id)
    {
        $user = $request->user();
        if (!$user || !$user->hasRole(['Admin', 'Editor', 'admin', 'editor'])) {
            abort(403);
        }

        $inscripcion = Inscripcion::with([
            'actividad:id,nombre,grabacion_id,modalidad_id',
            'actividad.modalidad:id,nombre',
            'actividad.grabacion:id,nombre,valor',
            'actividad.comidas:id,nombre,precio',
            'actividad.transportes:id,descripcion,precio',
            'actividad.hospedajes:id,nombre,precio,lugar_hospedaje_id',
            'actividad.hospedajes.lugarHospedaje:id,nombre',
            'comidas:id',
            'invitados.comidas:id',
            'invitados.transportes:id',
            'invitados.hospedajes:id',
        ])->findOrFail($id);

        $actividad = $inscripcion->actividad;
        $servicios = app(InscripcionServiciosService::class);
        // Disponibilidad de cupo por acomodación (excluye esta inscripción).
        $dispHospedajes = $actividad ? app(HospedajeCupoService::class)->disponibles($actividad, (int) $id) : [];

        return response()->json([
            'inscripcion' => [
                'id' => $inscripcion->id,
                'online' => (bool) $inscripcion->online,
                'pago' => $inscripcion->pago,
                'precioGeneral' => (float) $inscripcion->precioGeneral,
                'montoActividad' => (float) ($inscripcion->montoActividad ?? $inscripcion->precioGeneral),
                'incluye_grabacion' => $inscripcion->montoGrabacion !== null && (float) $inscripcion->montoGrabacion > 0,
                'comidas_ids' => $inscripcion->comidas->pluck('id')->values(),
                'transportes_ids' => $inscripcion->transporte_id ? [$inscripcion->transporte_id] : [],
                'hospedajes_ids' => $inscripcion->hospedaje_id ? [$inscripcion->hospedaje_id] : [],
            ],
            'actividad' => [
                'id' => $actividad?->id,
                'nombre' => $actividad?->nombre,
                'grabacion_id' => $actividad?->grabacion_id,
                'grabacion' => $actividad?->grabacion ? [
                    'id' => $actividad->grabacion->id,
                    'nombre' => $actividad->grabacion->nombre,
                    'valor' => (float) $actividad->grabacion->valor,
                ] : null,
                'comidas' => $actividad?->comidas->map(fn ($c) => ['id' => $c->id, 'nombre' => $c->nombre, 'precio' => (float) $c->precio])->values() ?? [],
                'transportes' => $actividad?->transportes->map(fn ($t) => ['id' => $t->id, 'descripcion' => $t->descripcion, 'precio' => (float) $t->precio])->values() ?? [],
                'hospedajes' => $actividad?->hospedajes->map(fn ($h) => ['id' => $h->id, 'nombre' => $h->nombre, 'precio' => (float) $h->precio, 'lugar_hospedaje' => $h->lugarHospedaje ? ['nombre' => $h->lugarHospedaje->nombre] : null, 'cantidad' => $h->pivot->cantidad, 'disponibles' => $dispHospedajes[$h->id] ?? null])->values() ?? [],
                'modalidad' => $actividad?->modalidad ? ['nombre' => $actividad->modalidad->nombre] : null,
            ],
            'modalidad_abierta' => $actividad ? $servicios->modalidadPermiteInvitadoOnline($actividad) : false,
            'invitados' => $inscripcion->invitados->map(fn ($inv) => [
                'id' => $inv->id,
                'nombre' => $inv->nombre,
                'apellido' => $inv->apellido,
                'telefono' => $inv->telefono,
                'online' => (bool) $inv->online,
                'incluye_grabacion' => (bool) $inv->incluye_grabacion,
                'comidas_ids' => $inv->comidas->pluck('id')->values(),
                'transportes_ids' => $inv->transportes->pluck('id')->values(),
                'hospedajes_ids' => $inv->hospedajes->pluck('id')->values(),
            ])->values(),
        ]);
    }

    /**
     * Edición admin de una inscripción: recalcula montos a partir de los servicios
     * del titular y de los invitados (precio general sin descuento). El monto NO se
     * escribe a mano: se recalcula siempre. (PUT, sólo admin/editor.)
     */
    public function update(Request $request, string $id)
    {
        $user = $request->user();
        if (!$user || !$user->hasRole(['Admin', 'Editor', 'admin', 'editor'])) {
            abort(403);
        }

        $data = $request->validate([
            'pago' => ['required', 'in:Saldado,Parcial,Pendiente'],
            'metodo_pago_id' => ['nullable', 'integer', 'exists:metodos_pago,id'],
            'monto_cobrado' => ['nullable', 'numeric', 'min:0'],
            'online' => ['nullable', 'boolean'],
            'incluye_grabacion' => ['nullable', 'boolean'],
            'comidas_ids' => ['nullable', 'array'],
            'comidas_ids.*' => ['integer', 'exists:comidas,id'],
            'transportes_ids' => ['nullable', 'array'],
            'transportes_ids.*' => ['integer', 'exists:transportes,id'],
            'hospedajes_ids' => ['nullable', 'array'],
            'hospedajes_ids.*' => ['integer', 'exists:hospedajes,id'],
            'invitados' => ['nullable', 'array', 'max:10'],
            'invitados.*.nombre' => ['required_with:invitados', 'string'],
            'invitados.*.apellido' => ['required_with:invitados', 'string'],
            'invitados.*.telefono' => ['nullable', 'string'],
            'invitados.*.online' => ['nullable', 'boolean'],
            'invitados.*.incluye_grabacion' => ['nullable', 'boolean'],
            'invitados.*.comidas_ids' => ['nullable', 'array'],
            'invitados.*.comidas_ids.*' => ['integer', 'exists:comidas,id'],
            'invitados.*.transportes_ids' => ['nullable', 'array'],
            'invitados.*.transportes_ids.*' => ['integer', 'exists:transportes,id'],
            'invitados.*.hospedajes_ids' => ['nullable', 'array'],
            'invitados.*.hospedajes_ids.*' => ['integer', 'exists:hospedajes,id'],
        ]);

        $servicios = app(InscripcionServiciosService::class);
        $cupo = app(HospedajeCupoService::class);
        $inscripcion = Inscripcion::with(['actividad.grabacion', 'actividad.modalidad'])->findOrFail($id);
        $actividad = $inscripcion->actividad;

        $incluyeGrabacion = (bool) ($data['incluye_grabacion'] ?? false);
        $comidasIds = array_values(array_unique(array_map('intval', $data['comidas_ids'] ?? [])));
        $transportesIds = array_values(array_unique(array_map('intval', $data['transportes_ids'] ?? [])));
        $hospedajesIds = array_values(array_unique(array_map('intval', $data['hospedajes_ids'] ?? [])));

        $montos = $servicios->montosServicios($actividad, $incluyeGrabacion, $comidasIds, $transportesIds, $hospedajesIds);

        // El precio de actividad del titular (según su membresía) no se recalcula aquí; se conserva.
        $montoActividad = (float) ($inscripcion->montoActividad ?? $inscripcion->precioGeneral);
        $precioGeneral = (float) $inscripcion->precioGeneral;

        $invitadosData = $servicios->prepararInvitados($actividad, $precioGeneral, $data['invitados'] ?? []);
        $montoInvitados = array_sum(array_column($invitadosData, 'montoapagar'));
        $hospedajeRequeridos = $cupo->requeridos($hospedajesIds, $invitadosData);

        $montoApagar = $montoActividad
            + (float) ($montos['montoGrabacion'] ?? 0)
            + (float) ($montos['montoComidas'] ?? 0)
            + (float) ($montos['montoTransporte'] ?? 0)
            + (float) ($montos['montoHospedaje'] ?? 0)
            + $montoInvitados;

        $pago = $data['pago'];
        // Sólo se eleva a "Confirmada" cuando queda saldada o sin saldo; nunca se degrada.
        $estado = ($pago === 'Saldado' || $montoApagar <= 0.0) ? 'Confirmada' : $inscripcion->estado;

        DB::transaction(function () use (
            $servicios, $cupo, $actividad, $hospedajeRequeridos, $inscripcion, $data, $montoActividad, $montos, $montoApagar, $montoInvitados,
            $pago, $estado, $comidasIds, $transportesIds, $hospedajesIds, $invitadosData, $user
        ) {
            $cupo->validar($actividad, $hospedajeRequeridos, $inscripcion->id);

            $inscripcion->update([
                'online' => array_key_exists('online', $data) ? (bool) $data['online'] : $inscripcion->online,
                'montoActividad' => $montoActividad,
                'montoGrabacion' => $montos['montoGrabacion'],
                'montoComidas' => $montos['montoComidas'],
                'montoTransporte' => $montos['montoTransporte'],
                'montoapagar' => $montoApagar,
                'monto_invitados' => $montoInvitados,
                'pago' => $pago,
                'estado' => $estado,
                'comida_id' => $comidasIds[0] ?? null,
                'transporte_id' => $transportesIds[0] ?? null,
                'hospedaje_id' => $hospedajesIds[0] ?? null,
                'auditoria_fecha' => now(),
                'auditor' => $user->id,
            ]);

            $inscripcion->comidas()->sync($comidasIds);
            $servicios->persistirInvitados($inscripcion, $invitadosData);
        });

        $this->registrarCobroAdmin($inscripcion, $data, $user->id);

        return response()->json([
            'ok' => true,
            'estado' => $inscripcion->estado,
            'montoapagar' => $montoApagar,
            'monto_invitados' => $montoInvitados,
        ]);
    }

    /**
     * Cambio rápido del estado de pago (no toca servicios ni invitados ni el monto).
     * Usado por el botón "marcar saldado". (PATCH, sólo admin/editor.)
     */
    public function marcarPago(Request $request, string $id)
    {
        $user = $request->user();
        if (!$user || !$user->hasRole(['Admin', 'Editor', 'admin', 'editor'])) {
            abort(403);
        }

        $data = $request->validate([
            'pago' => ['required', 'in:Saldado,Parcial,Pendiente'],
            'metodo_pago_id' => ['nullable', 'integer', 'exists:metodos_pago,id'],
            'monto_cobrado' => ['nullable', 'numeric', 'min:0'],
        ]);

        $inscripcion = Inscripcion::findOrFail($id);
        $inscripcion->pago = $data['pago'];
        if ($inscripcion->pago === 'Saldado' || (float) $inscripcion->montoapagar <= 0.0) {
            $inscripcion->estado = 'Confirmada';
        }
        $inscripcion->auditoria_fecha = now();
        $inscripcion->auditor = $user->id;
        $inscripcion->save();

        $this->registrarCobroAdmin($inscripcion, $data, $user->id);

        return response()->json(['ok' => true, 'estado' => $inscripcion->estado]);
    }

    /**
     * Registra en el ledger el importe recibido cuando el admin marca Saldado/Parcial.
     * No recalcula el estado (recalcular=false): el admin fija el label a mano.
     * Saldado sin monto explícito ⇒ toma el saldo pendiente; Parcial requiere monto_cobrado.
     * Sólo registra si el monto es > 0 (evita cobros duplicados al re-marcar Saldado).
     */
    private function registrarCobroAdmin(Inscripcion $inscripcion, array $data, int $userId): void
    {
        if (!in_array($data['pago'], ['Saldado', 'Parcial'], true)) {
            return;
        }

        $monto = isset($data['monto_cobrado']) && $data['monto_cobrado'] !== null
            ? (float) $data['monto_cobrado']
            : ($data['pago'] === 'Saldado' ? $inscripcion->saldoPendiente() : 0.0);

        if ($monto <= 0) {
            return;
        }

        $svc = app(CobroService::class);
        // Comprobantes subidos durante el checkout (quedan en inscripcion_comprobantes):
        // se enlazan TODOS al cobro al momento de crearlo (1 cobro : N comprobantes).
        $comprobanteIds = $inscripcion->comprobantes()->pluck('imagen_id')->all();

        $svc->registrar($inscripcion, [
            'monto' => $monto,
            'fecha_pago' => now()->toDateString(),
            'metodo_pago_id' => $data['metodo_pago_id'] ?? null,
            'comprobante_ids' => $comprobanteIds,
            'registrado_por' => $userId,
            'origen' => 'manual',
        ], recalcular: false);
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
    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        if (!$user || !$user->hasRole(['Admin', 'Editor', 'admin', 'editor'])) {
            abort(403);
        }

        $inscripcion = Inscripcion::with('comprobantes.imagen')->findOrFail($id);

        // Borrar los archivos de comprobantes del disco; las filas hijas
        // (inscripcion_comprobantes, inscripcion_comida) se eliminan por cascadeOnDelete.
        foreach ($inscripcion->comprobantes as $comprobante) {
            if ($comprobante->ruta) {
                Storage::disk('public')->delete($comprobante->ruta);
            }
        }

        $inscripcion->delete();

        return redirect()->back()->with('success', 'Inscripción eliminada.');
    }
}

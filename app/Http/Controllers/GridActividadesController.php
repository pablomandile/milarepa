<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Actividad;
use App\Models\Entidad;
use App\Models\GuestUser;
use App\Models\Inscripcion;
use App\Models\Invitado;
use App\Services\InscripcionServiciosService;
use App\Services\HospedajeCupoService;
use App\Services\InscripcionMailService;
use App\Services\MercadoPagoService;
use App\Models\Pais;
use App\Models\Provincia;
use App\Models\Municipio;
use App\Models\Barrio;
use App\Models\EnvioMail;
use App\Models\EmailEnvioConfiguracion;
use App\Models\User;
use App\Models\EstadoCuentaMembresia;
use App\Models\ConfiguracionSistema;
use App\Models\Moneda;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use App\Mail\InscripcionConfirmada;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;


class GridActividadesController extends Controller
{
    // Mensaje único de duplicado, usado por los guards de preparePago/pago/finalizarPago
    // y asserteado por tests. Para autenticados se le suma el hint de "Mis inscripciones".
    public const MSG_YA_INSCRIPTO = 'Ya estás inscripto a esta actividad.';

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sede = Entidad::resolverPorPalabra($request->query('sede'));
        $actividades = $this->actividadesParaGrilla($sede?->id);

        $paises = Pais::all();
        $provincias = Provincia::orderByRaw('FIELD(id, 24) DESC, id ASC')->get();
        $municipios = Municipio::all();
        $barrios = Barrio::all();

        // Obtener IDs de actividades donde el usuario actual está inscripto
        $userInscripcionesActividadIds = [];
        $userMembresiaActiva = null;
        if (auth()->check()) {
            /** @var User $authUser */
            $authUser = auth()->user();
            $userInscripcionesActividadIds = $authUser->inscripciones()->pluck('actividad_id')->toArray();
            $userMembresiaActiva = $this->resolverMembresiaActivaUsuario($authUser);
        }

        $gridVariante = ConfiguracionSistema::obtenerTexto('grid_actividades_variante', 'grid1');
        $override = $request->query('grid');
        if (in_array($override, ['grid1', 'grid2', '1', '2'], true) && auth()->check() && auth()->user()->hasRole('admin')) {
            $gridVariante = in_array($override, ['grid2', '2'], true) ? 'grid2' : 'grid1';
        }

        // dd($actividades->toArray());
        return inertia('GridActividades/Index', [
            'actividades' => $actividades->toArray(),
            'userInscripcionesActividadIds' => $userInscripcionesActividadIds,
            'userMembresiaActiva' => $userMembresiaActiva ? [
                'id' => $userMembresiaActiva->id,
                'nombre' => $userMembresiaActiva->nombre,
            ] : null,
            'paises' => $paises,
            'provincias' => $provincias,
            'municipios' => $municipios,
            'barrios' => $barrios,
            'gridVariante' => $gridVariante,
        ]);
    }

    /**
     * Actividades con las relaciones y el formato de fecha que esperan las cards de la grilla.
     * Con $entidadId se limita a esa sede (parámetro ?sede= de la URL pública).
     */
    private function actividadesParaGrilla(?int $entidadId = null): \Illuminate\Database\Eloquent\Collection
    {
        $with = [
            'tipoActividad',
            'descripcion',
            'imagen',
            'entidad',
            'disponibilidad',
            'modalidad',
            'esquemaPrecio.membresias.membresia',
            // La moneda de cada línea alimenta el precio de las cards: una actividad
            // con esquema en dólares no puede mostrarse con el símbolo de pesos.
            'esquemaPrecio.membresias.moneda:id,nombre,simbolo,es_principal',
            'esquemaDescuento.membresias.membresia',
            'esquemaDescuento.membresias.moneda:id,nombre,simbolo,es_principal',
            'stream',
            'grabacion',
            'programa',
            'metodosPago', 
            'hospedajes', 
            'comidas', 
            'transportes',
            'maestros',
            'coordinadores'
        ];
        if ($this->canUseLugarRelation()) {
            $with[] = 'lugar';
        }

        $actividades = Actividad::with($with)
            ->when($entidadId, fn ($q) => $q->where('entidad_id', $entidadId))
            ->get();

        // Convertir cada fecha con Carbon a un string amigable:
        $actividades->transform(function ($actividad) {
        // Si la columna es â€œfecha_inicioâ€, haz:
        $date = Carbon::parse($actividad->fecha_inicio);

        // Formato â€œ30 de Enero 00:00 hs.â€
        // â€œjâ€ = dÃ­a sin cero, â€œFâ€ = Mes completo, â€œH:iâ€ = 24h:mins
        $actividad->fecha_inicio_formateada = $date->translatedFormat('j \d\e F H:i') . ' hs.';

        return $actividad;
    });

        return $actividades;
    }

    /**
     * Versión embebible de la grilla (iframe en meditarenargentina.org).
     * Sin datos de sesión ni catálogos: "Inscribirme" y "Más info" abren
     * milarepa.com.ar en pestaña nueva desde la vista.
     */
    public function grillaEmbebida(Request $request)
    {
        $sede = Entidad::resolverPorPalabra($request->query('sede'));

        return inertia('GridActividades/GrillaEmbebida', [
            'actividades' => $this->actividadesParaGrilla($sede?->id)->toArray(),
            'gridVariante' => ConfiguracionSistema::obtenerTexto('grid_actividades_variante', 'grid1'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Buscar usuario por email para mostrar precios con descuento.
     */
    public function lookupEmail(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::with(['membresia', 'membresiaUsuario'])
            ->where('email', $data['email'])
            ->first();

        if (!$user) {
            return response()->json([
                'found' => false,
            ]);
        }

        $inscripcionesIds = $user->inscripciones()
            ->whereHas('actividad', function ($q) {
                $q->where('estado', true)->orWhere('estado', 1);
            })
            ->pluck('actividad_id')
            ->toArray();

        // Sumar inscripciones hechas como guest con este mismo email, para que el
        // aviso temprano del frontend ("ya estás inscripto") también las atrape.
        $emailNorm = mb_strtolower(trim($data['email']));
        $idsComoGuest = Inscripcion::whereHas('guestUser', fn ($g) => $g->whereRaw('LOWER(TRIM(email)) = ?', [$emailNorm]))
            ->whereHas('actividad', fn ($q) => $q->where('estado', true)->orWhere('estado', 1))
            ->pluck('actividad_id')
            ->toArray();
        $inscripcionesIds = array_values(array_unique(array_merge($inscripcionesIds, $idsComoGuest)));

        $membresiaActiva = $this->resolverMembresiaActivaUsuario($user);
        $membresiaRespuesta = $membresiaActiva ?: $user->membresia;

        return response()->json([
            'found' => true,
            'user' => [
                // Token opaco con TTL 15 min en lugar del id numérico.
                // El consumidor (preparePago / subscribePublic) lo descifra
                // con APP_KEY. Evita enumeración de IDs y IDOR ajeno.
                'lookup_token' => \App\Support\UserLookupToken::issue($user->id),
                'name' => $user->name,
                'email' => $user->email,
                'membresia' => $membresiaRespuesta ? [
                    'id' => $membresiaRespuesta->id,
                    'nombre' => $membresiaRespuesta->nombre,
                ] : null,
                'inscripciones_actividad_ids' => $inscripcionesIds,
            ],
        ]);
    }

    /**
     * Preparar datos de pago e inscripcion en sesion.
     */
    /**
     * Reglas de validación de los datos de un participante "guest" (no registrado).
     * Compartidas por el flujo público (preparePago) y el flujo admin
     * (EstadoInscripcionesController::crearInscripcionPrepare).
     *
     * @param string $prefijo Clave bajo la que viajan los datos (p.ej. "guest").
     * @param string $requeridoRegla Regla que marca obligatorios los campos núcleo
     *                               ("required_with:guest" en público; "required_if:..." en admin).
     */
    public static function reglasGuest(string $prefijo = 'guest', string $requeridoRegla = 'required_with:guest'): array
    {
        return [
            $prefijo => ['nullable', 'array'],
            "{$prefijo}.name" => [$requeridoRegla, 'string', 'max:255'],
            "{$prefijo}.email" => [$requeridoRegla, 'email', 'max:255'],
            "{$prefijo}.telefono" => ['nullable', 'string', 'max:50'],
            "{$prefijo}.whatsapp" => ['nullable', 'string', 'max:50'],
            "{$prefijo}.pais_id" => [$requeridoRegla, 'exists:paises,id'],
            "{$prefijo}.provincia_id" => [$requeridoRegla, 'exists:provincias,id'],
            "{$prefijo}.municipio_id" => ['nullable', 'exists:municipios,id'],
            "{$prefijo}.barrio_id" => ['nullable', 'exists:barrios,id'],
            "{$prefijo}.direccion" => ['nullable', 'string', 'max:255'],
            "{$prefijo}.msgxmail" => ['nullable', 'boolean'],
            "{$prefijo}.msgxwapp" => ['nullable', 'boolean'],
            "{$prefijo}.accesibilidad" => ['nullable', 'boolean'],
            "{$prefijo}.accesibilidad_desc" => ['nullable', 'string', 'max:255'],
            "{$prefijo}.info_tarjetas_kadampa" => ['nullable', 'boolean'],
            "{$prefijo}.registrar_datos" => ['nullable', 'boolean'],
        ];
    }

    public function preparePago(Request $request)
    {
        $data = $request->validate([
            'actividad_id' => ['required', 'exists:actividades,id'],
            // Token opaco emitido por lookupEmail (reemplaza user_id numérico).
            'user_lookup_token' => ['nullable', 'string'],
        ] + self::reglasGuest('guest', 'required_with:guest'));

        $guest = $data['guest'] ?? null;
        $registrarDatos = (bool) ($guest['registrar_datos'] ?? false);

        // Duplicado por email del guest (cubre inscripciones previas como guest
        // o como usuario registrado con ese mail). Va antes del chequeo de
        // "correo ya registrado" porque es el mensaje más específico.
        if (!empty($guest['email']) && $this->yaInscriptoEnActividad((int) $data['actividad_id'], null, $guest['email'])) {
            return response()->json(['ok' => false, 'message' => self::MSG_YA_INSCRIPTO], 422);
        }

        if ($registrarDatos && !empty($guest['email']) && User::where('email', $guest['email'])->exists()) {
            throw ValidationException::withMessages([
                'guest.email' => ['Este correo electrónico ya está registrado. Elegí "Ya estoy registrado" o iniciá sesión.'],
            ]);
        }

        $resolvedUserId = null;
        if (auth()->check() && empty($data['guest'])) {
            // Usuario logueado: ignora token, usa su propia sesión.
            $resolvedUserId = auth()->id();
        } elseif (!empty($data['user_lookup_token'])) {
            $resolvedUserId = \App\Support\UserLookupToken::resolveUserId($data['user_lookup_token']);
            if (!$resolvedUserId) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Token de identificación inválido o vencido. Volvé a verificar tu email.',
                ], 422);
            }
        }

        // Duplicado del usuario resuelto (token o sesión): corta ANTES de la
        // pantalla de pago, en vez del 422 tardío de finalizarPago.
        if ($resolvedUserId) {
            $resolvedEmail = User::find($resolvedUserId)?->email;
            if ($this->yaInscriptoEnActividad((int) $data['actividad_id'], $resolvedUserId, $resolvedEmail)) {
                $hint = auth()->check() ? ' Podés verla en "Mis inscripciones".' : '';

                return response()->json(['ok' => false, 'message' => self::MSG_YA_INSCRIPTO . $hint], 422);
            }
        }

        $request->session()->put('grid_pago', [
            'actividad_id' => $data['actividad_id'],
            'user_id' => $resolvedUserId,
            'guest' => $data['guest'] ?? null,
            'comprobante_path' => null,
            'pago_metodo' => null,
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Pantalla de pago.
     */
    public function pago(Request $request, Actividad $actividad, HospedajeCupoService $cupo)
    {
        $pago = $request->session()->get('grid_pago');
        if (!$pago || (int) $pago['actividad_id'] !== (int) $actividad->id) {
            if (auth()->check()) {
                $request->session()->put('grid_pago', [
                    'actividad_id' => $actividad->id,
                    'user_id' => auth()->id(),
                    'guest' => null,
                    'comprobante_path' => null,
                    'pago_metodo' => null,
                ]);
                $pago = $request->session()->get('grid_pago');
            } else {
                return redirect()->route('grid-actividades.index')
                    ->with('error', 'No hay datos de pago para esta actividad.');
            }
        }

        // Autenticado titular de la sesión y ya inscripto → afuera con mensaje.
        // Restringido a titularidad para no tocar el flujo admin (crearInscripcionPrepare
        // arma la sesión con el user_id de OTRA persona) ni el flujo update
        // (`inscripcion_id` presente, viene de "Pagar" en Mis inscripciones).
        if (empty($pago['inscripcion_id'])
            && auth()->check()
            && (int) ($pago['user_id'] ?? 0) === (int) auth()->id()
            && $this->yaInscriptoEnActividad((int) $actividad->id, (int) auth()->id(), auth()->user()->email)) {
            return redirect()->route('grid-actividades.index')
                ->with('error', self::MSG_YA_INSCRIPTO . ' Podés verla en "Mis inscripciones".');
        }

        $actividad->load([
            'metodosPago.imagen',
            'lugar',
            'entidad',
            'modalidad',
            'esquemaPrecio.membresias.membresia',
            'esquemaPrecio.membresias.moneda',
            'esquemaPrecio.membresias.botonPago',
            'esquemaDescuento.membresias.membresia',
            'esquemaDescuento.membresias.moneda',
            'esquemaDescuento.membresias.botonPago',
            'botonPago',
            'grabacion.botonPago',
            'comidas.botonPago',
            'transportes.botonPago',
            'hospedajes.botonPago',
            'hospedajes.lugarHospedaje',
            'grabacion.precios.moneda',
            'grabacion.precios.botonPago',
            'comidas.precios.moneda',
            'comidas.precios.botonPago',
            'transportes.precios.moneda',
            'transportes.precios.botonPago',
            'hospedajes.precios.moneda',
            'hospedajes.precios.botonPago',
        ]);

        // Precios por moneda de los servicios (accessor opt-in; el shape que
        // consume resolverPrecioItemEnMoneda en Pago.vue).
        $actividad->grabacion?->append('precios_por_moneda');
        $actividad->comidas->each->append('precios_por_moneda');
        $actividad->transportes->each->append('precios_por_moneda');
        $actividad->hospedajes->each->append('precios_por_moneda');

        // Disponibilidad de cupo por acomodación (excluye la inscripción en edición).
        $dispHospedajes = $cupo->disponibles($actividad, !empty($pago['inscripcion_id']) ? (int) $pago['inscripcion_id'] : null);
        $actividad->hospedajes->each(function ($h) use ($dispHospedajes) {
            $h->cantidad = $h->pivot->cantidad;
            $h->disponibles = $dispHospedajes[$h->id] ?? null;
        });

        $saldo = 0;
        $membresiaNombre = 'Sin membresía';
        $userContext = null;
        if (!empty($pago['user_id'])) {
            $user = User::with(['membresia', 'membresiaUsuario'])->find($pago['user_id']);
            if ($user) {
                $userContext = $user;
                [$precioGeneral, $precioMembresia, $membresiaNombre] = $this->calcularPrecios($actividad, $user);
                $saldo = $user->membresia_id ? $precioMembresia : $precioGeneral;
            }
        } else {
            [$precioGeneral] = $this->calcularPrecios($actividad, null);
            $saldo = $precioGeneral;
        }

        $botonPagoActividad = $this->obtenerBotonPagoActividad($actividad, $userContext);
        if ($botonPagoActividad) {
            $actividad->setRelation('botonPago', $botonPagoActividad);
        }

        $mostrarSelectorModalidad = $this->mostrarSelectorModalidadEnPago($actividad, $userContext);

        $inscripcionExistente = null;
        if (!empty($pago['inscripcion_id'])) {
            $inscripcionExistente = Inscripcion::with(['comidas'])
                ->where('id', $pago['inscripcion_id'])
                ->where('actividad_id', $actividad->id)
                ->first();
        }

        $monedaPrincipal = Moneda::principal();

        return inertia('GridActividades/Pago', [
            'actividad' => $actividad,
            'pago' => $pago,
            'saldo' => $saldo,
            'membresia' => $membresiaNombre,
            'membresiaId' => $userContext?->membresia_id,
            'mostrarSelectorModalidad' => $mostrarSelectorModalidad,
            'inscripcion' => $inscripcionExistente,
            'monedaPrincipal' => $monedaPrincipal
                ? ['id' => (int) $monedaPrincipal->id, 'nombre' => $monedaPrincipal->nombre, 'simbolo' => $monedaPrincipal->simbolo]
                : null,
            // Flujo update: la moneda queda fijada a la de la inscripción
            // original (null legacy = principal).
            'monedaInscripcion' => $inscripcionExistente
                ? (int) ($inscripcionExistente->moneda_id ?? $monedaPrincipal?->id)
                : null,
        ]);
    }

    /**
     * Subir comprobante y guardar path en sesion.
     */
    public function uploadComprobante(Request $request, \App\Services\OptimizadorImagenService $optimizador)
    {
        $request->validate([
            'comprobante' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:4096'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ], [
            'comprobante.max' => 'El comprobante supera el tamaño máximo permitido (4 MB).',
            'comprobante.mimes' => 'El comprobante debe ser PDF, JPG, PNG o WebP.',
        ]);

        $path = $optimizador->procesar($request->file('comprobante'), 'comprobantes');

        // Ojo: no se toca `pago_metodo`. El comprobante es opcional para todos los
        // medios de pago y es ortogonal al medio elegido por el usuario; la
        // persistencia en finalizarPago depende de `comprobante_path`, no del medio.
        $pago = $request->session()->get('grid_pago', []);
        $pago['comprobante_path'] = $path;
        $pago['comprobante_descripcion'] = $request->input('descripcion');
        $request->session()->put('grid_pago', $pago);

        return response()->json([
            'ok' => true,
            'path' => $path,
        ]);
    }

    /**
     * Finalizar inscripcion y enviar mail.
     */
    public function finalizarPago(Request $request, InscripcionServiciosService $servicios, HospedajeCupoService $cupo, InscripcionMailService $mailService, MercadoPagoService $mercadoPago)
    {
        $data = $request->validate([
            'pago_metodo' => ['required', 'string'],
            'moneda_id' => ['nullable', 'integer', 'exists:monedas,id'],
            'incluye_grabacion' => ['nullable', 'boolean'],
            'modalidad_cursada' => ['nullable', 'in:presencial,online'],
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

        $data['pago_metodo'] = $this->normalizarMetodoPagoFinal((string) ($data['pago_metodo'] ?? ''));

        $pago = $request->session()->get('grid_pago');
        if (!$pago) {
            return response()->json(['ok' => false, 'message' => 'No hay datos de pago.'], 422);
        }

        $actividad = Actividad::with([
            'modalidad',
            'esquemaPrecio.membresias',
            'esquemaPrecio.membresias.membresia',
            'esquemaDescuento.membresias',
            'esquemaDescuento.membresias.membresia',
            'grabacion',
            'comidas',
            'transportes',
            'hospedajes',
            'metodosPago',
        ])->findOrFail($pago['actividad_id']);

        // Los medios de pago son filas de `metodos_pago` (ABM), así que la lista
        // válida sale de la actividad y no de un whitelist fijo: de lo contrario
        // cada medio nuevo ("MercadoPago Alias", etc.) rompía el checkout con un 422.
        // Se suman las sentinelas internas que no son filas de la tabla.
        // pluck() devuelve una Collection base: map() sobre la Eloquent Collection
        // conservaria su clase y unique() reventaria al tratar strings como modelos.
        $metodosValidos = $actividad->metodosPago
            ->pluck('nombre')
            ->map(fn ($nombre) => $this->normalizarMetodoPagoFinal((string) $nombre))
            ->push('efectivo')
            ->push('comprobante')
            ->unique()
            ->all();

        if (!in_array($data['pago_metodo'], $metodosValidos, true)) {
            return response()->json([
                'ok' => false,
                'message' => 'The selected pago metodo is invalid.',
            ], 422);
        }

        // Inscripción en edición ("Pagar" de Mis inscripciones / flujo admin): se
        // resuelve ANTES del cálculo porque el update conserva la moneda original
        // (el selector del front viaja deshabilitado en ese flujo).
        $inscripcionExistente = null;
        if (!empty($pago['inscripcion_id'])) {
            $inscripcionExistente = Inscripcion::where('id', $pago['inscripcion_id'])
                ->where('actividad_id', $actividad->id)
                ->first();

            if (!$inscripcionExistente) {
                return response()->json(['ok' => false, 'message' => 'No se encontró la inscripción a actualizar.'], 404);
            }
        }

        $monedaPrincipalId = Moneda::principalId();
        $monedaIdSeleccionada = isset($data['moneda_id']) ? (int) $data['moneda_id'] : null;
        $monedaIdEfectiva = $inscripcionExistente
            ? (int) ($inscripcionExistente->moneda_id ?? $monedaPrincipalId)
            : ($monedaIdSeleccionada ?? $monedaPrincipalId);

        // La moneda tiene que ser la principal o una del esquema vigente
        // (evita cotizar en una moneda arbitraria por POST manual).
        if ($monedaIdEfectiva !== null && $monedaIdEfectiva !== $monedaPrincipalId) {
            $monedasEsquema = $this->obtenerEsquemaPrecioVigente($actividad)?->membresias
                ?->pluck('moneda_id')->map(fn ($id) => (int) $id)->all() ?? [];
            if (!in_array($monedaIdEfectiva, $monedasEsquema, true)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'La moneda seleccionada no está disponible para esta actividad.',
                ], 422);
            }
        }

        // Mercado Pago (Argentina) solo procesa pesos: en otra moneda quedan
        // transferencias/botones de pago, comprobante y efectivo.
        if ($data['pago_metodo'] === 'mercadopago'
            && $monedaPrincipalId !== null
            && $monedaIdEfectiva !== null
            && $monedaIdEfectiva !== $monedaPrincipalId) {
            return response()->json([
                'ok' => false,
                'message' => 'Mercado Pago solo está disponible para pagos en pesos.',
            ], 422);
        }

        // Guard de duplicados ANTES de crear User/GuestUser (si no, el flujo guest
        // creaba un GuestUser nuevo por intento y el chequeo nunca matcheaba).
        // Excluye la inscripción en edición para no bloquear el flujo update.
        $dedupUserId = !empty($pago['user_id']) ? (int) $pago['user_id'] : null;
        $dedupEmail = $pago['guest']['email'] ?? ($dedupUserId ? User::find($dedupUserId)?->email : null);
        $ignorarId = !empty($pago['inscripcion_id']) ? (int) $pago['inscripcion_id'] : null;
        if ($this->yaInscriptoEnActividad((int) $actividad->id, $dedupUserId, $dedupEmail, $ignorarId)) {
            return response()->json(['ok' => false, 'message' => self::MSG_YA_INSCRIPTO], 422);
        }

        $user = null;
        $guestUser = null;
        $registrado = false;

        if (!empty($pago['user_id'])) {
            $user = User::with(['membresia', 'membresiaUsuario'])->findOrFail($pago['user_id']);
            $registrado = true;
        } elseif (!empty($pago['guest'])) {
            $guest = $pago['guest'];
            $registrarDatos = (bool) ($guest['registrar_datos'] ?? false);
            if ($registrarDatos) {
                $user = User::firstOrCreate(
                    ['email' => $guest['email']],
                    [
                        'name' => $guest['name'],
                        'password' => null,
                        'telefono' => $guest['telefono'] ?? null,
                        'whatsapp' => $guest['whatsapp'] ?? null,
                        'pais_id' => $guest['pais_id'],
                        'provincia_id' => $guest['provincia_id'],
                        'municipio_id' => $guest['municipio_id'] ?? null,
                        'barrio_id' => $guest['barrio_id'] ?? null,
                        'direccion' => $guest['direccion'] ?? null,
                        'msgxmail' => (bool) ($guest['msgxmail'] ?? false),
                        'msgxwapp' => (bool) ($guest['msgxwapp'] ?? false),
                        'accesibilidad' => (bool) ($guest['accesibilidad'] ?? false),
                        'accesibilidad_desc' => $guest['accesibilidad_desc'] ?? null,
                    ]
                );
                $user->updateMembresiaUsuario([
                    'membresia_id' => $user->membresia_id,
                    'membresia_inscripcion_fecha' => $user->membresia_inscripcion_fecha,
                    'membresia_online' => (bool) ($user->membresia_online ?? false),
                    'membresia_online_motivo' => $user->membresia_online_motivo,
                    'info_tarjetas_kadampa' => (bool) ($guest['info_tarjetas_kadampa'] ?? false),
                    'envioInfoTk' => $user->envioInfoTk,
                ]);
                $user->syncRoles(['asistant']);
                $registrado = true;
            } else {
                $guestUser = GuestUser::create([
                    'name' => $guest['name'],
                    'email' => $guest['email'],
                    'telefono' => $guest['telefono'] ?? null,
                    'whatsapp' => $guest['whatsapp'] ?? null,
                    'pais_id' => $guest['pais_id'],
                    'provincia_id' => $guest['provincia_id'],
                    'municipio_id' => $guest['municipio_id'] ?? null,
                    'barrio_id' => $guest['barrio_id'] ?? null,
                    'direccion' => $guest['direccion'] ?? null,
                    'msgxmail' => (bool) ($guest['msgxmail'] ?? false),
                    'msgxwapp' => (bool) ($guest['msgxwapp'] ?? false),
                    'accesibilidad' => (bool) ($guest['accesibilidad'] ?? false),
                    'accesibilidad_desc' => $guest['accesibilidad_desc'] ?? null,
                    'info_tarjetas_kadampa' => (bool) ($guest['info_tarjetas_kadampa'] ?? false),
                ]);
                $user = $this->ensureGuestOwner();
            }
        }

        if (!$user) {
            return response()->json(['ok' => false, 'message' => 'No hay usuario para registrar.'], 422);
        }

        [$precioGeneral, $precioMembresia, $membresiaNombre] = $this->calcularPrecios(
            $actividad,
            $registrado ? $user : null,
            $monedaIdEfectiva
        );
        $montoActividad = (float) (($user && $user->membresia_id) ? $precioMembresia : $precioGeneral);
        $online = $this->resolverModalidadOnline($actividad, $user, $registrado, Arr::get($data, 'modalidad_cursada'));
        $incluyeGrabacion = (bool) ($data['incluye_grabacion'] ?? false);
        $envioLinkStream = $actividad->stream_id ? 'Pendiente' : 'No aplica';
        $envioGrabacion = $actividad->grabacion_id ? 'Pendiente' : 'No aplica';
        $comidasIds = $data['comidas_ids'] ?? [];
        $transportesIds = $data['transportes_ids'] ?? [];
        $hospedajesIds = $data['hospedajes_ids'] ?? [];
        $comidaId = $comidasIds[0] ?? null;
        $transporteId = $transportesIds[0] ?? null;
        $hospedajeId = $hospedajesIds[0] ?? null;

        $montos = $servicios->montosServicios($actividad, $incluyeGrabacion, $comidasIds, $transportesIds, $hospedajesIds, $monedaIdEfectiva);
        $montoGrabacion = $montos['montoGrabacion'];
        $montoComidas = $montos['montoComidas'];
        $montoTransporte = $montos['montoTransporte'];
        $montoHospedaje = $montos['montoHospedaje'];

        $invitadosData = $servicios->prepararInvitados($actividad, (float) $precioGeneral, $data['invitados'] ?? [], $monedaIdEfectiva);
        $montoInvitados = array_sum(array_column($invitadosData, 'montoapagar'));
        $hospedajeRequeridos = $cupo->requeridos($hospedajesIds, $invitadosData);

        // Total dividido: montoapagar es la porción en la moneda de la inscripción;
        // los servicios sin precio en esa moneda se cobran en la principal.
        $montoApagar = $montoActividad
            + (float) ($montoGrabacion ?? 0)
            + (float) ($montoComidas ?? 0)
            + (float) ($montoTransporte ?? 0)
            + (float) ($montoHospedaje ?? 0)
            + $montoInvitados;
        $montoMonedaPrincipal = (float) ($montos['montoPrincipal'] ?? 0)
            + array_sum(array_map(fn ($inv) => (float) ($inv['monto_moneda_principal'] ?? 0), $invitadosData));
        $montoMonedaPrincipal = $montoMonedaPrincipal > 0 ? $montoMonedaPrincipal : null;
        // Gratis solo si AMBAS porciones son cero.
        [$estadoPago, $estadoInscripcion] = $this->resolverEstadoSegunMonto($montoApagar + (float) ($montoMonedaPrincipal ?? 0));

        if ($inscripcionExistente) {
            $inscripcion = $inscripcionExistente;

            DB::transaction(function () use (
                $servicios, $cupo, $actividad, $hospedajeRequeridos, $inscripcion, $membresiaNombre, $precioGeneral, $montoActividad, $montoGrabacion,
                $montoTransporte, $montoComidas, $montoHospedaje, $montoApagar, $montoInvitados, $monedaIdEfectiva, $montoMonedaPrincipal, $estadoPago, $estadoInscripcion,
                $envioLinkStream, $envioGrabacion, $online, $hospedajeId, $comidaId, $transporteId,
                $comidasIds, $invitadosData, $pago
            ) {
                $cupo->validar($actividad, $hospedajeRequeridos, $inscripcion->id);

                $inscripcion->update([
                    'membresia' => $membresiaNombre,
                    'precioGeneral' => $precioGeneral,
                    'montoActividad' => $montoActividad,
                    'montoGrabacion' => $montoGrabacion,
                    'montoTransporte' => $montoTransporte,
                    'montoComidas' => $montoComidas,
                    'montoHospedaje' => $montoHospedaje,
                    'montoapagar' => $montoApagar,
                    'moneda_id' => $monedaIdEfectiva,
                    'monto_moneda_principal' => $montoMonedaPrincipal,
                    'monto_invitados' => $montoInvitados,
                    'pago' => $estadoPago,
                    'estado' => $estadoInscripcion,
                    'envioLinkStream' => $envioLinkStream,
                    'envioGrabacion' => $envioGrabacion,
                    'online' => $online,
                    'hospedaje_id' => $hospedajeId,
                    'comida_id' => $comidaId,
                    'transporte_id' => $transporteId,
                ]);

                $inscripcion->comidas()->sync($comidasIds);
                $servicios->persistirInvitados($inscripcion, $invitadosData);

                if (!empty($pago['comprobante_path'])) {
                    // El comprobante del checkout entra como cobro "a revisar".
                    $svc = app(\App\Services\CobroService::class);
                    $svc->registrarComprobanteARevisar(
                        $inscripcion,
                        $svc->resolverComprobanteId($pago['comprobante_path']),
                        $pago['comprobante_descripcion'] ?? null,
                        origen: 'checkout',
                    );
                }
            });

            $request->session()->forget('grid_pago');

            if ($data['pago_metodo'] === 'mercadopago') {
                return $this->iniciarPagoMercadoPago($inscripcion, true, true, $mercadoPago);
            }

            return response()->json([
                'ok' => true,
                'inscripcion_id' => $inscripcion->id,
                'registered' => true,
                'can_view_private' => true,
                'updated_existing' => true,
            ]);
        }

        $inscripcion = DB::transaction(function () use (
            $servicios, $cupo, $hospedajeRequeridos, $actividad, $user, $guestUser, $membresiaNombre, $precioGeneral, $montoActividad, $montoGrabacion,
            $montoTransporte, $montoComidas, $montoHospedaje, $montoApagar, $montoInvitados, $monedaIdEfectiva, $montoMonedaPrincipal, $estadoPago, $estadoInscripcion,
            $envioLinkStream, $envioGrabacion, $online, $hospedajeId, $comidaId, $transporteId,
            $comidasIds, $invitadosData, $pago
        ) {
            $cupo->validar($actividad, $hospedajeRequeridos, null);

            $inscripcion = Inscripcion::create([
                'actividad_id' => $actividad->id,
                'user_id' => $user->id,
                'guest_user_id' => $guestUser?->id,
                'membresia' => $membresiaNombre,
                'precioGeneral' => $precioGeneral,
                'montoActividad' => $montoActividad,
                'montoGrabacion' => $montoGrabacion,
                'montoTransporte' => $montoTransporte,
                'montoComidas' => $montoComidas,
                'montoHospedaje' => $montoHospedaje,
                'montoapagar' => $montoApagar,
                'moneda_id' => $monedaIdEfectiva,
                'monto_moneda_principal' => $montoMonedaPrincipal,
                'monto_invitados' => $montoInvitados,
                'pago' => $estadoPago,
                'estado' => $estadoInscripcion,
                'envioLinkStream' => $envioLinkStream,
                'envioRegistro' => 'Pendiente',
                'envioConfirmacion' => 'Pendiente',
                'envioGrabacion' => $envioGrabacion,
                'asistencia' => 'Pendiente',
                'online' => $online,
                'hospedaje_id' => $hospedajeId,
                'comida_id' => $comidaId,
                'transporte_id' => $transporteId,
            ]);

            if (!empty($comidasIds)) {
                $inscripcion->comidas()->sync($comidasIds);
            }

            $servicios->persistirInvitados($inscripcion, $invitadosData);

            if (!empty($pago['comprobante_path'])) {
                // El comprobante del checkout entra como cobro "a revisar".
                $svc = app(\App\Services\CobroService::class);
                $svc->registrarComprobanteARevisar(
                    $inscripcion,
                    $svc->resolverComprobanteId($pago['comprobante_path']),
                    $pago['comprobante_descripcion'] ?? null,
                    origen: 'checkout',
                );
            }

            return $inscripcion;
        });

        $inscripcionRelations = [
            'actividad.entidad',
            'actividad.imagen',
            'actividad.descripcion',
            'actividad.modalidad',
            'actividad.stream.links',
            'user',
            'guestUser',
        ];
        if ($this->canUseLugarRelation()) {
            $inscripcionRelations[] = 'actividad.lugar';
        }
        $inscripcion->load($inscripcionRelations);

        $mailService->enviarConfirmacionRegistro($inscripcion);

        $solicitaInfoTk = (bool) Arr::get($pago, 'guest.info_tarjetas_kadampa', false);
        if (!$solicitaInfoTk && $registrado) {
            $solicitaInfoTk = (bool) ($user->info_tarjetas_kadampa ?? false);
        }

        if ($solicitaInfoTk) {
            $destinatarioInfoTk = $guestUser?->email ?: $user?->email;
            if ($destinatarioInfoTk) {
                try {
                    $configuracionInfoTk = EmailEnvioConfiguracion::resolverPlantilla('informacion_membresias');

                    Mail::to($destinatarioInfoTk)->send(
                        new InscripcionConfirmada($inscripcion, $configuracionInfoTk['view'])
                    );

                    EnvioMail::create([
                        'fecha' => now()->toDateString(),
                        'tipo' => 'Automático',
                        'user_id' => null,
                        'destinatario' => $destinatarioInfoTk,
                        'motivo' => $configuracionInfoTk['nombre'],
                    ]);

                    $fechaEnvio = now();
                    if ($guestUser) {
                        $guestUser->envioInfoTk = $fechaEnvio;
                        $guestUser->save();
                    } elseif ($user) {
                        $user->updateMembresiaUsuario([
                            'membresia_id' => $user->membresia_id,
                            'membresia_inscripcion_fecha' => $user->membresia_inscripcion_fecha,
                            'membresia_online' => (bool) ($user->membresia_online ?? false),
                            'membresia_online_motivo' => $user->membresia_online_motivo,
                            'info_tarjetas_kadampa' => (bool) ($user->info_tarjetas_kadampa ?? false),
                            'envioInfoTk' => $fechaEnvio,
                        ]);
                    }
                } catch (\Exception $e) {
                    // Ignorar error de mail de info TK
                }
            }
        }

        $request->session()->forget('grid_pago');

        if ($data['pago_metodo'] === 'mercadopago') {
            return $this->iniciarPagoMercadoPago($inscripcion, $registrado, false, $mercadoPago);
        }

        return response()->json([
            'ok' => true,
            'inscripcion_id' => $inscripcion->id,
            'registered' => $registrado,
            'can_view_private' => auth()->check(),
            'public_url' => URL::temporarySignedRoute(
                'grid-actividades.inscripcion',
                now()->addDays(180),
                ['inscripcion' => $inscripcion->id]
            ),
        ]);
    }

    /**
     * Retorno desde el checkout de Mercado Pago (back_urls). El estado real del
     * pago lo confirma el webhook; acá solo llevamos al usuario a la landing de su
     * inscripción (URL firmada), que mostrará el estado actualizado.
     */
    public function pagoRetorno(Inscripcion $inscripcion)
    {
        $signedUrl = URL::temporarySignedRoute(
            'grid-actividades.inscripcion',
            now()->addDays(180),
            ['inscripcion' => $inscripcion->id]
        );

        return redirect()->to($signedUrl);
    }

    /**
     * Landing pÃºblico de inscripcion.
     */
    public function showPublic(Inscripcion $inscripcion)
    {
        $inscripcionLoad = [
            'actividad.entidad',
            'actividad.descripcion',
            'actividad.imagen',
            'actividad.modalidad',
            'actividad.tipoActividad',
            'hospedaje',
            'comida',
            'comidas',
            'transporte',
            'cobros.comprobantes.imagen',
            'invitados',
            'invitados.comidas',
            'invitados.transportes',
            'invitados.hospedajes',
        ];
        if ($this->canUseLugarRelation()) {
            $inscripcionLoad[] = 'actividad.lugar';
        }
        $inscripcion->load($inscripcionLoad);

        // La vista consume `comprobantes` plano; ahora viven en los cobros.
        $inscripcion->setRelation('comprobantes', $inscripcion->comprobantesDeCobros());
        $inscripcion->unsetRelation('cobros');

        if (!empty($inscripcion->actividad) && !empty($inscripcion->actividad->fecha_inicio)) {
            try {
                $date = Carbon::parse($inscripcion->actividad->fecha_inicio);
                $inscripcion->actividad->fecha_inicio_formateada = $date->translatedFormat('j \d\e F H:i') . ' hs.';
            } catch (\Exception $e) {
                $inscripcion->actividad->fecha_inicio_formateada = $inscripcion->actividad->fecha_inicio;
            }
        }

        return inertia('GridActividades/Inscripcion', [
            'inscripcion' => $inscripcion,
        ]);
    }

    /**
     * Show pÃºblica de una actividad (sin login).
     */
    public function showPublicActividad(Request $request, Actividad $actividad)
    {
        $showPublicLoad = [
            'imagen',
            'entidad',
            'descripcion',
            'programa',
            'modalidad',
            'tipoActividad',
            'maestros.imagen',
            'grabacion',
            'comidas',
            'transportes',
            'hospedajes',
            'esquemaPrecio.membresias.membresia',
            'esquemaPrecio.membresias.moneda:id,nombre,simbolo,es_principal',
            'esquemaDescuento.membresias.membresia',
            'esquemaDescuento.membresias.moneda:id,nombre,simbolo,es_principal',
        ];
        if ($this->canUseLugarRelation()) {
            $showPublicLoad[] = 'lugar';
        }
        $actividad->load($showPublicLoad);

        $paises = Pais::all();
        $provincias = Provincia::orderByRaw('FIELD(id, 24) DESC, id ASC')->get();
        $municipios = Municipio::all();
        $barrios = Barrio::all();

        $userInscripcionesActividadIds = [];
        $userMembresiaActiva = null;
        if (auth()->check()) {
            $authUser = auth()->user();
            $userInscripcionesActividadIds = Inscripcion::where('user_id', auth()->id())
                ->pluck('actividad_id');
            if ($authUser) {
                $userMembresiaActiva = $this->resolverMembresiaActivaUsuario($authUser);
            }
        }

        if (!empty($actividad->fecha_inicio)) {
            try {
                $date = Carbon::parse($actividad->fecha_inicio);
                $actividad->fecha_inicio_formateada = $date->translatedFormat('j \d\e F H:i') . ' hs.';
            } catch (\Exception $e) {
                $actividad->fecha_inicio_formateada = $actividad->fecha_inicio;
            }
        }

        return inertia('GridActividades/ShowPublic', [
            'actividad' => $actividad,
            'paises' => $paises,
            'provincias' => $provincias,
            'municipios' => $municipios,
            'barrios' => $barrios,
            'userInscripcionesActividadIds' => $userInscripcionesActividadIds,
            'userMembresiaActiva' => $userMembresiaActiva ? [
                'id' => $userMembresiaActiva->id,
                'nombre' => $userMembresiaActiva->nombre,
            ] : null,
            'returnUrl' => $request->query('return_url'),
        ]);
    }

    /**
     * Inscribir usuario conocido (login o lookup por email).
     */
    public function inscribir(Request $request)
    {
        $data = $request->validate([
            'actividad_id' => ['required', 'exists:actividades,id'],
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $actividad = Actividad::with([
            'modalidad',
            'esquemaPrecio.membresias',
            'esquemaPrecio.membresias.membresia',
            'esquemaDescuento.membresias',
            'esquemaDescuento.membresias.membresia',
        ])->findOrFail($data['actividad_id']);

        $user = User::with(['membresia', 'membresiaUsuario'])->findOrFail($data['user_id']);

        $yaInscripto = Inscripcion::where('user_id', $user->id)
            ->where('actividad_id', $actividad->id)
            ->exists();
        if ($yaInscripto) {
            return response()->json([
                'ok' => false,
                'message' => 'Ya estás inscripto a esta actividad.',
            ], 422);
        }

        [$precioGeneral, $precioMembresia, $membresiaNombre] = $this->calcularPrecios($actividad, $user);
        $montoApagar = $user->membresia_id ? $precioMembresia : $precioGeneral;

        $online = ($actividad->modalidad?->nombre === 'Online')
            || ($user && $user->membresia_id && $user->membresia_online);
        $envioLinkStream = $actividad->stream_id ? 'Pendiente' : 'No aplica';
        $envioGrabacion = $actividad->grabacion_id ? 'Pendiente' : 'No aplica';
        [$estadoPago, $estadoInscripcion] = $this->resolverEstadoSegunMonto($montoApagar);
        $inscripcion = Inscripcion::create([
            'actividad_id' => $actividad->id,
            'user_id' => $user->id,
            'membresia' => $membresiaNombre,
            'precioGeneral' => $precioGeneral,
            'montoActividad' => $montoApagar,
            'montoGrabacion' => null,
            'montoTransporte' => null,
            'montoComidas' => null,
            'montoapagar' => $montoApagar,
            'pago' => $estadoPago,
            'estado' => $estadoInscripcion,
            'envioLinkStream' => $envioLinkStream,
                'envioRegistro' => 'Pendiente',
                'envioConfirmacion' => 'Pendiente',
            'envioGrabacion' => $envioGrabacion,
            'asistencia' => 'Pendiente',
            'online' => $online,
            'hospedaje_id' => null,
            'comida_id' => null,
            'transporte_id' => null,
        ]);

        return response()->json([
            'ok' => true,
            'inscripcion_id' => $inscripcion->id,
        ]);
    }

    /**
     * Inscribir invitado con form y registro opcional.
     */
    public function inscribirGuest(Request $request)
    {
        $data = $request->validate([
            'actividad_id' => ['required', 'exists:actividades,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'pais_id' => ['required', 'exists:paises,id'],
            'provincia_id' => ['required', 'exists:provincias,id'],
            'municipio_id' => ['nullable', 'exists:municipios,id'],
            'barrio_id' => ['nullable', 'exists:barrios,id'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'msgxmail' => ['nullable', 'boolean'],
            'msgxwapp' => ['nullable', 'boolean'],
            'accesibilidad' => ['nullable', 'boolean'],
            'accesibilidad_desc' => ['nullable', 'string', 'max:255'],
            'info_tarjetas_kadampa' => ['nullable', 'boolean'],
            'registrar_datos' => ['nullable', 'boolean'],
        ]);

        $actividad = Actividad::with([
            'modalidad',
            'esquemaPrecio.membresias',
            'esquemaPrecio.membresias.membresia',
            'esquemaDescuento.membresias',
            'esquemaDescuento.membresias.membresia',
        ])->findOrFail($data['actividad_id']);

        $registrarDatos = (bool) ($data['registrar_datos'] ?? false);

        if ($registrarDatos && User::where('email', $data['email'])->exists()) {
            throw ValidationException::withMessages([
                'email' => ['Este correo electrónico ya está registrado. Elegí "Ya estoy registrado" o iniciá sesión.'],
            ]);
        }

        if ($registrarDatos) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => null,
                    'telefono' => $data['telefono'] ?? null,
                    'whatsapp' => $data['whatsapp'] ?? null,
                    'pais_id' => $data['pais_id'],
                    'provincia_id' => $data['provincia_id'],
                    'municipio_id' => $data['municipio_id'] ?? null,
                    'barrio_id' => $data['barrio_id'] ?? null,
                    'direccion' => $data['direccion'] ?? null,
                    'msgxmail' => (bool) ($data['msgxmail'] ?? false),
                    'msgxwapp' => (bool) ($data['msgxwapp'] ?? false),
                    'accesibilidad' => (bool) ($data['accesibilidad'] ?? false),
                    'accesibilidad_desc' => $data['accesibilidad_desc'] ?? null,
                ]
            );

            $user->updateMembresiaUsuario([
                'membresia_id' => $user->membresia_id,
                'membresia_inscripcion_fecha' => $user->membresia_inscripcion_fecha,
                'membresia_online' => (bool) ($user->membresia_online ?? false),
                'membresia_online_motivo' => $user->membresia_online_motivo,
                'info_tarjetas_kadampa' => (bool) ($data['info_tarjetas_kadampa'] ?? false),
                'envioInfoTk' => $user->envioInfoTk,
            ]);

            $user->syncRoles(['asistant']);

            $yaInscripto = Inscripcion::where('user_id', $user->id)
                ->where('actividad_id', $actividad->id)
                ->exists();
            if ($yaInscripto) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Ya estás inscripto a esta actividad.',
                ], 422);
            }

            [$precioGeneral, $precioMembresia, $membresiaNombre] = $this->calcularPrecios($actividad, $user);

            $montoApagar = $user->membresia_id ? $precioMembresia : $precioGeneral;
            $online = ($actividad->modalidad?->nombre === 'Online')
                || ($user && $user->membresia_id && $user->membresia_online);
            $envioLinkStream = $actividad->stream_id ? 'Pendiente' : 'No aplica';
            $envioGrabacion = $actividad->grabacion_id ? 'Pendiente' : 'No aplica';
            [$estadoPago, $estadoInscripcion] = $this->resolverEstadoSegunMonto($montoApagar);
            $inscripcion = Inscripcion::create([
                'actividad_id' => $actividad->id,
                'user_id' => $user->id,
                'membresia' => $membresiaNombre,
                'precioGeneral' => $precioGeneral,
                'montoActividad' => $montoApagar,
                'montoGrabacion' => null,
                'montoTransporte' => null,
                'montoComidas' => null,
                'montoapagar' => $montoApagar,
                'pago' => $estadoPago,
                'estado' => $estadoInscripcion,
                'envioLinkStream' => $envioLinkStream,
                'envioRegistro' => 'Pendiente',
                'envioConfirmacion' => 'Pendiente',
                'envioGrabacion' => $envioGrabacion,
                'asistencia' => 'Pendiente',
                'online' => $online,
                'hospedaje_id' => null,
                'comida_id' => null,
                'transporte_id' => null,
            ]);

            return response()->json([
                'ok' => true,
                'inscripcion_id' => $inscripcion->id,
                'registered' => true,
            ]);
        }

        $guestUser = GuestUser::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'telefono' => $data['telefono'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'pais_id' => $data['pais_id'],
            'provincia_id' => $data['provincia_id'],
            'municipio_id' => $data['municipio_id'] ?? null,
            'barrio_id' => $data['barrio_id'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'msgxmail' => (bool) ($data['msgxmail'] ?? false),
            'msgxwapp' => (bool) ($data['msgxwapp'] ?? false),
            'accesibilidad' => (bool) ($data['accesibilidad'] ?? false),
            'accesibilidad_desc' => $data['accesibilidad_desc'] ?? null,
            'info_tarjetas_kadampa' => (bool) ($data['info_tarjetas_kadampa'] ?? false),
        ]);

        $guestOwner = $this->ensureGuestOwner();
        [$precioGeneral, $precioMembresia, $membresiaNombre] = $this->calcularPrecios($actividad, null);

        $montoApagar = $precioGeneral;
        $envioLinkStream = $actividad->stream_id ? 'Pendiente' : 'No aplica';
        $envioGrabacion = $actividad->grabacion_id ? 'Pendiente' : 'No aplica';
        [$estadoPago, $estadoInscripcion] = $this->resolverEstadoSegunMonto($montoApagar);
        $inscripcion = Inscripcion::create([
            'actividad_id' => $actividad->id,
            'user_id' => $guestOwner->id,
            'guest_user_id' => $guestUser->id,
            'membresia' => $membresiaNombre,
            'precioGeneral' => $precioGeneral,
            'montoActividad' => $montoApagar,
            'montoGrabacion' => null,
            'montoTransporte' => null,
            'montoComidas' => null,
            'montoapagar' => $montoApagar,
            'pago' => $estadoPago,
            'estado' => $estadoInscripcion,
            'envioLinkStream' => $envioLinkStream,
                'envioRegistro' => 'Pendiente',
                'envioConfirmacion' => 'Pendiente',
            'envioGrabacion' => $envioGrabacion,
            'asistencia' => 'Pendiente',
            'online' => $actividad->modalidad?->nombre === 'Online',
            'hospedaje_id' => null,
            'comida_id' => null,
            'transporte_id' => null,
        ]);

        return response()->json([
            'ok' => true,
            'inscripcion_id' => $inscripcion->id,
            'registered' => false,
        ]);
    }

    private function ensureGuestOwner(): User
    {
        $email = 'guest@milarepa.local';

        return User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Invitado',
                'password' => Hash::make(Str::random(32)),
            ]
        );
    }

    /**
     * ¿Ya existe una inscripción a la actividad para este usuario y/o email?
     * Dedup por `user_id` (solo inscripciones de usuarios reales: las guest
     * comparten el user owner guest@milarepa.local, de ahí el whereNull) y por
     * email normalizado contra users Y guest_users (mismo criterio que el dedup
     * del importador multievento). `$ignorarInscripcionId` excluye la inscripción
     * en edición (flujo "Pagar" de Mis inscripciones, que actualiza en vez de crear).
     * Nota: chequeo check-then-act sin unique en BD (imposible por el guest owner);
     * la ventana de carrera entre requests simultáneos es de ms y se acepta.
     */
    private function yaInscriptoEnActividad(int $actividadId, ?int $userId, ?string $email, ?int $ignorarInscripcionId = null): bool
    {
        $emailNorm = $email !== null ? mb_strtolower(trim($email)) : null;
        if (!$userId && ($emailNorm === null || $emailNorm === '')) {
            return false;
        }

        return Inscripcion::where('actividad_id', $actividadId)
            ->when($ignorarInscripcionId, fn ($q) => $q->where('id', '!=', $ignorarInscripcionId))
            ->where(function ($q) use ($userId, $emailNorm) {
                if ($userId) {
                    $q->orWhere(fn ($s) => $s->where('user_id', $userId)->whereNull('guest_user_id'));
                }
                if ($emailNorm) {
                    $q->orWhere(fn ($s) => $s->whereNull('guest_user_id')
                        ->whereHas('user', fn ($u) => $u->whereRaw('LOWER(TRIM(email)) = ?', [$emailNorm])));
                    $q->orWhereHas('guestUser', fn ($g) => $g->whereRaw('LOWER(TRIM(email)) = ?', [$emailNorm]));
                }
            })
            ->exists();
    }

    private function calcularPrecios(Actividad $actividad, ?User $user, ?int $monedaId = null): array
    {
        $actividad->loadMissing([
            'esquemaPrecio.membresias.membresia',
            'esquemaPrecio.membresias.moneda',
            'esquemaDescuento.membresias.membresia',
            'esquemaDescuento.membresias.moneda',
        ]);

        $precioGeneral = 0;
        $precioMembresia = 0;
        $membresiaNombre = 'Sin membresía';
        $esquemaVigente = $this->obtenerEsquemaPrecioVigente($actividad);

        if ($esquemaVigente?->membresias) {
            $general = $this->resolverLineaEsquema($esquemaVigente->membresias, null, $monedaId)
                ?? $esquemaVigente->membresias
                    ->first(fn ($linea) => $this->esMembresiaGeneral($linea?->membresia?->nombre));
            $precioGeneral = $general->precio ?? 0;
        }

        if ($user && $user->membresia_id && $esquemaVigente?->membresias) {
            $pivot = $this->resolverLineaEsquema($esquemaVigente->membresias, (int) $user->membresia_id, $monedaId)
                ?? $esquemaVigente->membresias->firstWhere('membresia_id', $user->membresia_id);
            $precioMembresia = $pivot->precio ?? 0;
            $membresiaNombre = $user->membresia?->nombre ?? $membresiaNombre;
        }

        return [$precioGeneral, $precioMembresia, $membresiaNombre];
    }

    private function obtenerEsquemaPrecioVigente(Actividad $actividad)
    {
        if ($this->aplicaDescuentoAnticipado($actividad) && $actividad->esquemaDescuento) {
            return $actividad->esquemaDescuento;
        }

        return $actividad->esquemaPrecio;
    }

    private function obtenerBotonPagoActividad(Actividad $actividad, ?User $user)
    {
        $actividad->loadMissing([
            'esquemaPrecio.membresias.membresia',
            'esquemaPrecio.membresias.botonPago',
            'esquemaDescuento.membresias.membresia',
            'esquemaDescuento.membresias.botonPago',
            'botonPago',
        ]);

        $esquemaVigente = $this->obtenerEsquemaPrecioVigente($actividad);
        if (!$esquemaVigente?->membresias) {
            return $actividad->botonPago;
        }

        $linea = null;
        if ($user && $user->membresia_id) {
            $linea = $esquemaVigente->membresias->firstWhere('membresia_id', $user->membresia_id);
        }

        if (!$linea) {
            $linea = $esquemaVigente->membresias
                ->first(fn ($item) => $this->esMembresiaGeneral($item?->membresia?->nombre));
        }

        return $linea?->botonPago ?: $actividad->botonPago;
    }

    private function aplicaDescuentoAnticipado(Actividad $actividad): bool
    {
        if (empty($actividad->pagoAmticipado)) {
            return false;
        }

        try {
            $limite = Carbon::parse($actividad->pagoAmticipado);
        } catch (\Exception $e) {
            return false;
        }

        return now()->lte($limite);
    }

    private function esMembresiaGeneral(?string $nombre): bool
    {
        $normalized = mb_strtolower(trim((string) $nombre), 'UTF-8');
        $normalized = strtr($normalized, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
        ]);

        return str_contains($normalized, 'sin membres');
    }

    private function resolverLineaEsquema($lineas, ?int $membresiaId, ?int $monedaId)
    {
        if (!$lineas || $lineas->isEmpty()) {
            return null;
        }

        if ($membresiaId && $monedaId) {
            $exacta = $lineas->first(function ($linea) use ($membresiaId, $monedaId) {
                return (int) ($linea->membresia_id ?? 0) === $membresiaId
                    && (int) ($linea->moneda_id ?? 0) === $monedaId;
            });
            if ($exacta) {
                return $exacta;
            }
        }

        if ($monedaId) {
            $generalMoneda = $lineas->first(function ($linea) use ($monedaId) {
                return (int) ($linea->moneda_id ?? 0) === $monedaId
                    && $this->esMembresiaGeneral($linea?->membresia?->nombre);
            });
            if ($generalMoneda) {
                return $generalMoneda;
            }
        }

        if ($membresiaId) {
            $membresiaCualquieraMoneda = $lineas->first(function ($linea) use ($membresiaId) {
                return (int) ($linea->membresia_id ?? 0) === $membresiaId;
            });
            if ($membresiaCualquieraMoneda) {
                return $membresiaCualquieraMoneda;
            }
        }

        return $lineas->first(fn ($linea) => $this->esMembresiaGeneral($linea?->membresia?->nombre))
            ?? $lineas->first();
    }

    private function mostrarSelectorModalidadEnPago(Actividad $actividad, ?User $user): bool
    {
        $modalidad = $this->normalizarTextoModalidad($actividad->modalidad?->nombre);

        if ($modalidad === 'presencial y online abierta') {
            return true;
        }

        if ($modalidad === 'presencial y online') {
            return (bool) ($user?->membresia_online ?? false);
        }

        return false;
    }

    private function resolverModalidadOnline(Actividad $actividad, ?User $user, bool $registrado, ?string $modalidadCursada): bool
    {
        $modalidad = $this->normalizarTextoModalidad($actividad->modalidad?->nombre);

        if ($modalidad === 'online') {
            return true;
        }

        $seleccion = strtolower((string) ($modalidadCursada ?? 'presencial'));
        if (!in_array($seleccion, ['presencial', 'online'], true)) {
            $seleccion = 'presencial';
        }

        if ($modalidad === 'presencial y online abierta') {
            return $seleccion === 'online';
        }

        if ($modalidad === 'presencial y online') {
            $puedeElegirOnline = $registrado && (bool) ($user?->membresia_online ?? false);
            return $puedeElegirOnline && $seleccion === 'online';
        }

        return false;
    }

    private function normalizarTextoModalidad(?string $texto): string
    {
        $normalized = mb_strtolower(trim((string) $texto), 'UTF-8');
        return strtr($normalized, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
        ]);
    }

    private function normalizarMetodoPagoFinal(string $metodo): string
    {
        $normalizado = mb_strtolower(trim($metodo), 'UTF-8');
        $normalizado = strtr($normalizado, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'Ã¡' => 'a',
            'Ã©' => 'e',
            'Ã­' => 'i',
            'Ã³' => 'o',
            'Ãº' => 'u',
        ]);

        if (in_array($normalizado, ['tarjeta de credito', 'tarjeta de debito', 'credito', 'debito'], true)) {
            return 'efectivo';
        }

        if ($normalizado === 'gratis') {
            return 'efectivo';
        }

        if (str_contains($normalizado, 'qr')) {
            return 'qr';
        }

        if (in_array($normalizado, ['mercado pago', 'mercadopago', 'mercado-pago'], true)) {
            return 'mercadopago';
        }

        return $normalizado;
    }

    private function resolverEstadoSegunMonto(float $montoApagar): array
    {
        if ($montoApagar <= 0.0) {
            return ['Saldado', 'Confirmada'];
        }

        return ['Pendiente', 'Registrada'];
    }

    /**
     * Crea la preferencia de Mercado Pago para la inscripción y devuelve la
     * respuesta con la URL del checkout a la que debe redirigir el frontend.
     * La confirmación del pago la realiza el webhook (no este retorno).
     */
    private function iniciarPagoMercadoPago(Inscripcion $inscripcion, bool $registrado, bool $updatedExisting, MercadoPagoService $mercadoPago)
    {
        try {
            $redirectUrl = $mercadoPago->crearPreferencia($inscripcion);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => 'No se pudo iniciar el pago con Mercado Pago. Intentá nuevamente en unos minutos.',
            ], 502);
        }

        return response()->json([
            'ok' => true,
            'inscripcion_id' => $inscripcion->id,
            'registered' => $registrado,
            'redirect_url' => $redirectUrl,
            'updated_existing' => $updatedExisting,
        ]);
    }

    private function resolverMembresiaActivaUsuario(User $user)
    {
        $estadoActivo = EstadoCuentaMembresia::query()
            ->with('membresia')
            ->where('user_id', $user->id)
            ->where('estado', EstadoCuentaMembresia::ESTADO_ACTIVA)
            ->orderByDesc('mes_pagado')
            ->orderByDesc('created_at')
            ->first();

        return $estadoActivo?->membresia;
    }

    private function canUseLugarRelation(): bool
    {
        return Schema::hasTable('lugares') && Schema::hasColumn('actividades', 'lugar_id');
    }
}


<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Actividad;
use App\Models\GuestUser;
use App\Models\Inscripcion;
use App\Models\Pais;
use App\Models\Provincia;
use App\Models\Municipio;
use App\Models\Barrio;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\InscripcionConfirmada;


class GridActividadesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $actividades = Actividad::with([
            'tipoActividad',
            'descripcion',
            'imagen',
            'entidad',
            'disponibilidad',
            'modalidad',
            'esquemaPrecio.membresias.membresia',
            'esquemaDescuento',
            'stream',
            'grabacion',
            'programa',
            'metodosPago', 
            'hospedajes', 
            'comidas', 
            'transportes',
            'maestros',
            'coordinadores'
        ])->get();

        $paises = Pais::all();
        $provincias = Provincia::orderByRaw('FIELD(id, 24) DESC, id ASC')->get();
        $municipios = Municipio::all();
        $barrios = Barrio::all();

        // Convertir cada fecha con Carbon a un string amigable:
        $actividades->transform(function ($actividad) {
        // Si la columna es “fecha_inicio”, haz:
        $date = Carbon::parse($actividad->fecha_inicio);

        // Formato “30 de Enero 00:00 hs.”
        // “j” = día sin cero, “F” = Mes completo, “H:i” = 24h:mins
        $actividad->fecha_inicio_formateada = $date->translatedFormat('j \d\e F H:i') . ' hs.';

        return $actividad;
    });

        // Obtener IDs de actividades donde el usuario actual está inscripto
        $userInscripcionesActividadIds = [];
        if (auth()->check()) {
            $userInscripcionesActividadIds = auth()->user()->inscripciones()->pluck('actividad_id')->toArray();
        }

        // dd($actividades->toArray());
        return inertia('GridActividades/Index', [
            'actividades' => $actividades->toArray(),
            'userInscripcionesActividadIds' => $userInscripcionesActividadIds,
            'paises' => $paises,
            'provincias' => $provincias,
            'municipios' => $municipios,
            'barrios' => $barrios,
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

        $user = User::with('membresia')
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

        return response()->json([
            'found' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'membresia' => $user->membresia ? [
                    'id' => $user->membresia->id,
                    'nombre' => $user->membresia->nombre,
                ] : null,
                'inscripciones_actividad_ids' => $inscripcionesIds,
            ],
        ]);
    }

    /**
     * Preparar datos de pago e inscripción en sesión.
     */
    public function preparePago(Request $request)
    {
        $data = $request->validate([
            'actividad_id' => ['required', 'exists:actividades,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'guest' => ['nullable', 'array'],
            'guest.name' => ['required_with:guest', 'string', 'max:255'],
            'guest.email' => ['required_with:guest', 'email', 'max:255'],
            'guest.telefono' => ['nullable', 'string', 'max:50'],
            'guest.whatsapp' => ['nullable', 'string', 'max:50'],
            'guest.pais_id' => ['required_with:guest', 'exists:paises,id'],
            'guest.provincia_id' => ['required_with:guest', 'exists:provincias,id'],
            'guest.municipio_id' => ['nullable', 'exists:municipios,id'],
            'guest.barrio_id' => ['nullable', 'exists:barrios,id'],
            'guest.direccion' => ['nullable', 'string', 'max:255'],
            'guest.msgxmail' => ['nullable', 'boolean'],
            'guest.msgxwapp' => ['nullable', 'boolean'],
            'guest.accesibilidad' => ['nullable', 'boolean'],
            'guest.accesibilidad_desc' => ['nullable', 'string', 'max:255'],
            'guest.registrar_datos' => ['nullable', 'boolean'],
        ]);

        $request->session()->put('grid_pago', [
            'actividad_id' => $data['actividad_id'],
            'user_id' => $data['user_id'] ?? null,
            'guest' => $data['guest'] ?? null,
            'comprobante_path' => null,
            'pago_metodo' => null,
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Pantalla de pago.
     */
    public function pago(Request $request, Actividad $actividad)
    {
        $pago = $request->session()->get('grid_pago');
        if (!$pago || (int) $pago['actividad_id'] !== (int) $actividad->id) {
            return redirect()->route('grid-actividades.index')
                ->with('error', 'No hay datos de pago para esta actividad.');
        }

        $actividad->load([
            'metodosPago',
            'esquemaPrecio.membresias.membresia',
            'botonPago',
            'grabacion.botonPago',
            'comidas.botonPago',
            'transportes.botonPago',
            'hospedajes.botonPago',
            'hospedajes.lugarHospedaje',
        ]);
        $saldo = 0;
        $membresiaNombre = 'Sin membresía';
        if (!empty($pago['user_id'])) {
            $user = User::with('membresia')->find($pago['user_id']);
            if ($user) {
                [$precioGeneral, $precioMembresia, $membresiaNombre] = $this->calcularPrecios($actividad, $user);
                $saldo = $user->membresia_id ? $precioMembresia : $precioGeneral;
            }
        } else {
            [$precioGeneral] = $this->calcularPrecios($actividad, null);
            $saldo = $precioGeneral;
        }

        return inertia('GridActividades/Pago', [
            'actividad' => $actividad,
            'pago' => $pago,
            'saldo' => $saldo,
            'membresia' => $membresiaNombre,
        ]);
    }

    /**
     * Subir comprobante y guardar path en sesión.
     */
    public function uploadComprobante(Request $request)
    {
        $request->validate([
            'comprobante' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
        ], [
            'comprobante.max' => 'El comprobante supera el tamaño máximo permitido (4 MB).',
            'comprobante.mimes' => 'El comprobante debe ser PDF, JPG o PNG.',
        ]);

        $path = $request->file('comprobante')->store('comprobantes', 'public');

        $pago = $request->session()->get('grid_pago', []);
        $pago['comprobante_path'] = $path;
        $pago['pago_metodo'] = 'comprobante';
        $request->session()->put('grid_pago', $pago);

        return response()->json([
            'ok' => true,
            'path' => $path,
        ]);
    }

    /**
     * Finalizar inscripción y enviar mail.
     */
    public function finalizarPago(Request $request)
    {
        $data = $request->validate([
            'pago_metodo' => ['required', 'in:efectivo,comprobante,transferencia'],
        ]);

        $pago = $request->session()->get('grid_pago');
        if (!$pago) {
            return response()->json(['ok' => false, 'message' => 'No hay datos de pago.'], 422);
        }

        $actividad = Actividad::with([
            'modalidad',
            'esquemaPrecio.membresias',
            'esquemaPrecio.membresias.membresia',
        ])->findOrFail($pago['actividad_id']);

        $user = null;
        $guestUser = null;
        $registrado = false;

        if (!empty($pago['user_id'])) {
            $user = User::with('membresia')->findOrFail($pago['user_id']);
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
                ]);
                $user = $this->ensureGuestOwner();
            }
        }

        if (!$user) {
            return response()->json(['ok' => false, 'message' => 'No hay usuario para registrar.'], 422);
        }

        $yaInscriptoQuery = Inscripcion::where('actividad_id', $actividad->id);
        if ($guestUser) {
            $yaInscriptoQuery->where('guest_user_id', $guestUser->id);
        } else {
            $yaInscriptoQuery->where('user_id', $user->id);
        }
        $yaInscripto = $yaInscriptoQuery->exists();
        if ($yaInscripto) {
            return response()->json(['ok' => false, 'message' => 'Ya estás inscripto a esta actividad.'], 422);
        }

        [$precioGeneral, $precioMembresia, $membresiaNombre] = $this->calcularPrecios($actividad, $registrado ? $user : null);
        $estadoPago = $data['pago_metodo'] === 'comprobante' ? 'Saldado' : 'Pendiente';
        $montoApagar = ($user && $user->membresia_id) ? $precioMembresia : $precioGeneral;
        $online = ($actividad->modalidad?->nombre === 'Online')
            || ($user && $user->membresia_id && $user->membresia_online);
        $envioLinkStream = $actividad->stream_id ? 'pendiente' : 'No aplica';
        $envioGrabacion = $actividad->grabacion_id ? 'pendiente' : 'No aplica';
        $inscripcion = Inscripcion::create([
            'actividad_id' => $actividad->id,
            'user_id' => $user->id,
            'guest_user_id' => $guestUser?->id,
            'membresia' => $membresiaNombre,
            'precioGeneral' => $precioGeneral,
            'montoapagar' => $montoApagar,
            'pago' => $estadoPago,
            'estado_id' => 1,
            'envioLinkStream' => $envioLinkStream,
            'envioGrabación' => $envioGrabacion,
            'comprobante' => $pago['comprobante_path'],
            'asistencia' => 'ausente',
            'online' => $online,
            'hospedaje_id' => null,
            'comida_id' => null,
            'transporte_id' => null,
        ]);

        if ($registrado) {
            $inscripcion->load([
                'actividad.entidad',
                'actividad.descripcion',
                'actividad.modalidad',
                'user',
                'estado',
            ]);
            try {
                Mail::to($user->email)->send(new InscripcionConfirmada($inscripcion));
            } catch (\Exception $e) {
                // Ignorar error de mail
            }
        }

        $request->session()->forget('grid_pago');

        return response()->json([
            'ok' => true,
            'inscripcion_id' => $inscripcion->id,
            'registered' => $registrado,
        ]);
    }

    /**
     * Landing público de inscripción.
     */
    public function showPublic(Inscripcion $inscripcion)
    {
        $inscripcion->load([
            'actividad.entidad',
            'actividad.descripcion',
            'actividad.imagen',
            'actividad.modalidad',
            'actividad.tipoActividad',
            'estado',
            'hospedaje',
            'comida',
            'transporte',
        ]);

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
     * Show pública de una actividad (sin login).
     */
    public function showPublicActividad(Actividad $actividad)
    {
        $actividad->load([
            'imagen',
            'entidad',
            'descripcion',
            'modalidad',
            'tipoActividad',
            'maestros',
            'esquemaPrecio.membresias.membresia',
        ]);

        $paises = Pais::all();
        $provincias = Provincia::orderByRaw('FIELD(id, 24) DESC, id ASC')->get();
        $municipios = Municipio::all();
        $barrios = Barrio::all();

        $userInscripcionesActividadIds = [];
        if (auth()->check()) {
            $userInscripcionesActividadIds = Inscripcion::where('user_id', auth()->id())
                ->pluck('actividad_id');
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
        ])->findOrFail($data['actividad_id']);

        $user = User::with('membresia')->findOrFail($data['user_id']);

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
        $envioLinkStream = $actividad->stream_id ? 'pendiente' : 'No aplica';
        $envioGrabacion = $actividad->grabacion_id ? 'pendiente' : 'No aplica';
        $inscripcion = Inscripcion::create([
            'actividad_id' => $actividad->id,
            'user_id' => $user->id,
            'membresia' => $membresiaNombre,
            'precioGeneral' => $precioGeneral,
            'montoapagar' => $montoApagar,
            'pago' => 'Pendiente',
            'estado_id' => 1,
            'envioLinkStream' => $envioLinkStream,
            'envioGrabación' => $envioGrabacion,
            'comprobante' => null,
            'asistencia' => 'ausente',
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
            'registrar_datos' => ['nullable', 'boolean'],
        ]);

        $actividad = Actividad::with([
            'modalidad',
            'esquemaPrecio.membresias',
            'esquemaPrecio.membresias.membresia',
        ])->findOrFail($data['actividad_id']);

        $registrarDatos = (bool) ($data['registrar_datos'] ?? false);

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
            $envioLinkStream = $actividad->stream_id ? 'pendiente' : 'No aplica';
            $envioGrabacion = $actividad->grabacion_id ? 'pendiente' : 'No aplica';
            $inscripcion = Inscripcion::create([
                'actividad_id' => $actividad->id,
                'user_id' => $user->id,
                'membresia' => $membresiaNombre,
                'precioGeneral' => $precioGeneral,
                'montoapagar' => $montoApagar,
                'pago' => 'Pendiente',
                'estado_id' => 1,
                'envioLinkStream' => $envioLinkStream,
                'envioGrabación' => $envioGrabacion,
                'comprobante' => null,
                'asistencia' => 'ausente',
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
        ]);

        $guestOwner = $this->ensureGuestOwner();
        [$precioGeneral, $precioMembresia, $membresiaNombre] = $this->calcularPrecios($actividad, null);

        $montoApagar = $precioGeneral;
        $envioLinkStream = $actividad->stream_id ? 'pendiente' : 'No aplica';
        $envioGrabacion = $actividad->grabacion_id ? 'pendiente' : 'No aplica';
        $inscripcion = Inscripcion::create([
            'actividad_id' => $actividad->id,
            'user_id' => $guestOwner->id,
            'guest_user_id' => $guestUser->id,
            'membresia' => $membresiaNombre,
            'precioGeneral' => $precioGeneral,
            'montoapagar' => $montoApagar,
            'pago' => 'Pendiente',
            'estado_id' => 1,
            'envioLinkStream' => $envioLinkStream,
            'envioGrabación' => $envioGrabacion,
            'comprobante' => null,
            'asistencia' => 'ausente',
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

    private function calcularPrecios(Actividad $actividad, ?User $user): array
    {
        $precioGeneral = 0;
        $precioMembresia = 0;
        $membresiaNombre = 'Sin membresía';

        if ($actividad->esquemaPrecio?->membresias) {
            $general = $actividad->esquemaPrecio->membresias
                ->firstWhere('membresia.nombre', 'Sin membresía');
            $precioGeneral = $general->precio ?? 0;
        }

        if ($user && $user->membresia_id && $actividad->esquemaPrecio?->membresias) {
            $pivot = $actividad->esquemaPrecio->membresias
                ->firstWhere('membresia_id', $user->membresia_id);
            $precioMembresia = $pivot->precio ?? 0;
            $membresiaNombre = $user->membresia?->nombre ?? $membresiaNombre;
        }

        return [$precioGeneral, $precioMembresia, $membresiaNombre];
    }
}



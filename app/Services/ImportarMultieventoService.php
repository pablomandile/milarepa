<?php

namespace App\Services;

use App\Models\Actividad;
use App\Models\Entidad;
use App\Models\Inscripcion;
use App\Models\Membresia;
use App\Models\MultieventoMapeo;
use App\Models\User;
use App\Services\Concerns\ParseaCsvInscripciones;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Importa inscripciones desde la planilla maestra consolidada (multi-evento).
 *
 * - La fuente puede ser un CSV subido o la descarga de la planilla de Google Sheets
 *   configurada (config/multievento.php).
 * - El ruteo fila → actividad es semi-manual: se detectan los eventos del archivo,
 *   se sugiere una actividad (por el texto entre comillas de NombreEvento + fecha) y
 *   el admin confirma/elige el destino de cada evento antes de importar.
 * - Solo se importan filas con FechaEvento >= fecha de corte (config).
 * - Identidad: se matchea/crea el usuario por email. Si el email ya pertenece a otra
 *   persona (nombre distinto) se crea un usuario nuevo con un email placeholder no
 *   funcional y determinístico ({local}.i.{nombre}.{apellido}@import.local), para que
 *   re-subir el archivo resuelva al mismo usuario (idempotencia).
 * - Dedupe por actividad + usuario resuelto.
 */
class ImportarMultieventoService
{
    use ParseaCsvInscripciones;

    /** Dominio no entregable para los emails placeholder de personas que comparten email. */
    private const DOMINIO_PLACEHOLDER = 'import.local';

    /**
     * Registro intra-corrida: email real => nombre normalizado de la primera persona
     * que lo "reclamó". Permite resolver el placeholder de forma consistente en el
     * preview (donde todavía no se crean usuarios) y en la importación.
     *
     * @var array<string,string>
     */
    private array $emailReal = [];

    /** Mapeo: clave interna => header del archivo (se compara normalizado). */
    private const COLUMNAS = [
        'nombre'            => 'Nombre',
        'apellido'          => 'Apellido',
        'email'             => 'email',
        'celular'           => 'Celular',
        'telefono_wa'       => 'Telefono_WA',
        'barrio'            => 'BarrioNormalizado',
        'medio'             => 'MedioComunicacion',
        'membresia'         => 'Membresia',
        'recibir_info'      => 'RecibirInfoTk',
        'pendiente'         => 'Pendiente',
        'valor'             => 'Valor',
        'fecha_pago'        => 'FechaPago',
        'forma'             => 'Forma',
        'pago'              => 'Pago',
        'asistencia'        => 'Asistencia',
        'modalidad'         => 'Modalidad',
        'marca_temporal'    => 'Marca_temporal',
        'nombre_evento'     => 'NombreEvento',
        'fecha_evento'      => 'FechaEvento',
        'comentarios'       => 'Comentarios',
        'confirmado_manual' => 'Confirmado_Manual',
    ];

    /** Headers conocidos que no se usan (no se reportan como desconocidos). */
    private const COLUMNAS_IGNORADAS = [
        'Id_Inscripcion',
        'ID_EventoClase',
        'FechaApertura',
        'Anexo',
        'Origen',
        'Origen_Inscripcion',
        'Coordinador',
        'Id_LoteMultiple',
        'ID_Practicante',
        'BarrioPcia',
        'Tiene_tk',
        '__SourceFileId',
        '__NombreArchivoOrigen',
        '__RowId',
        '__Hash',
        '__LastSync',
    ];

    /**
     * @param  array<string,int>  $mapeo  clave de evento => actividad_id confirmado
     */
    public function previsualizar(string $contenido, array $mapeo = []): array
    {
        return $this->procesar($contenido, $mapeo, false);
    }

    /**
     * @param  array<string,int>  $mapeo  clave de evento => actividad_id confirmado
     */
    public function importar(string $contenido, array $mapeo): array
    {
        return $this->procesar($contenido, $mapeo, true);
    }

    /**
     * Descarga el CSV de la planilla configurada (Google Sheets, accesible por link).
     */
    public function descargarPlanilla(): string
    {
        $url = (string) config('multievento.sheet_url');
        $id = $this->extraerSpreadsheetId($url);
        if (!$id) {
            throw new \RuntimeException('La URL de la planilla configurada no es válida (no se pudo extraer el ID).');
        }
        $gid = (string) config('multievento.sheet_gid', '0');
        $export = "https://docs.google.com/spreadsheets/d/{$id}/export?format=csv&gid={$gid}";

        $resp = Http::timeout(30)->get($export);
        if (!$resp->ok()) {
            throw new \RuntimeException('No se pudo descargar la planilla (HTTP ' . $resp->status() . ').');
        }

        $body = $resp->body();
        $ct = strtolower((string) $resp->header('Content-Type'));
        if (str_contains($ct, 'text/html') || str_starts_with(ltrim($body), '<')) {
            throw new \RuntimeException(
                'La planilla no es accesible públicamente. Compartila como "cualquiera con el enlace: lector" o publicá la pestaña.'
            );
        }

        return $body;
    }

    public function urlPlanilla(): string
    {
        return (string) config('multievento.sheet_url');
    }

    private function extraerSpreadsheetId(string $url): ?string
    {
        return preg_match('#/spreadsheets/d/([a-zA-Z0-9_\-]+)#', $url, $m) ? $m[1] : null;
    }

    private function procesar(string $contenido, array $mapeo, bool $ejecutar): array
    {
        [$filas, $columnasDesconocidas] = $this->mapearCsv($contenido, self::COLUMNAS, self::COLUMNAS_IGNORADAS);
        $ctx = $this->contexto();
        $this->emailReal = [];

        // Pass 1: agrupar eventos (solo filas que pasan el corte por fecha) + sugerir actividad.
        $eventos = $this->detectarEventos($filas, $ctx);

        $resultado = [
            'total_filas'           => count($filas),
            'fecha_corte'           => $ctx['fechaCorte']->toDateString(),
            'descartadas_fecha'     => 0,
            'sin_actividad'         => 0,
            'a_crear'               => 0,
            'usuarios_nuevos'       => 0,
            'usuarios_existentes'   => 0,
            'omitidas'              => 0,
            'errores'               => 0,
            'creadas'               => 0,
            'columnas_desconocidas' => $columnasDesconocidas,
            'eventos'               => [],
            'filas'                 => [],
        ];

        $vistos = [];

        $run = function () use ($filas, $eventos, $mapeo, $ctx, $ejecutar, &$vistos, &$resultado) {
            foreach ($filas as $index => $fila) {
                $info = $this->analizarFila($fila, $eventos, $mapeo, $ctx, $vistos, $ejecutar);
                $info['linea'] = $index + 2; // +1 header, +1 base-1
                $resultado['filas'][] = $info;

                switch ($info['accion']) {
                    case 'descartada_fecha':
                        $resultado['descartadas_fecha']++;
                        break;
                    case 'sin_actividad':
                        $resultado['sin_actividad']++;
                        break;
                    case 'error':
                        $resultado['errores']++;
                        break;
                    case 'omitir':
                        $resultado['omitidas']++;
                        break;
                    case 'crear':
                        $resultado['a_crear']++;
                        $info['user_existe'] ? $resultado['usuarios_existentes']++ : $resultado['usuarios_nuevos']++;
                        if ($ejecutar) {
                            $this->crearInscripcion($info, $ctx);
                            $resultado['creadas']++;
                        }
                        break;
                }
            }

            // Recordar los matches evento→actividad confirmados para futuras importaciones.
            if ($ejecutar) {
                $this->guardarMapeos($eventos, $mapeo, $ctx);
            }
        };

        if ($ejecutar) {
            DB::transaction($run);
        } else {
            $run();
        }

        // Armar el resumen de eventos para la UI (con la actividad asignada efectiva).
        foreach ($eventos as $clave => $ev) {
            $resultado['eventos'][] = [
                'clave'             => $clave,
                'nombre_evento'     => $ev['nombre_evento'],
                'fecha_evento'      => $ev['fecha_evento'],
                'filas'             => $ev['filas'],
                'sugerida_id'       => $ev['sugerida_id'],
                'sugerida_nombre'   => $ev['sugerida_nombre'],
                'origen_sugerencia' => $ev['origen_sugerencia'],
                'asignada_id'       => array_key_exists($clave, $mapeo) ? (int) $mapeo[$clave] : $ev['sugerida_id'],
            ];
        }

        return $resultado;
    }

    /** Carga datos de referencia una sola vez. */
    private function contexto(): array
    {
        $entidades = Entidad::select('id', 'nombre', 'abreviacion', 'entidad_principal')->get();

        // Emails ya inscriptos por actividad (para dedupe).
        $inscritos = [];
        Inscripcion::query()
            ->select('actividad_id', 'user_id', 'guest_user_id')
            ->with(['user:id,email', 'guestUser:id,email'])
            ->get()
            ->each(function ($i) use (&$inscritos) {
                foreach ([optional($i->user)->email, optional($i->guestUser)->email] as $e) {
                    if ($e) {
                        $inscritos[$i->actividad_id][strtolower(trim($e))] = true;
                    }
                }
            });

        return [
            'actividades'      => Actividad::select('id', 'nombre', 'fecha_inicio', 'entidad_id')->get(),
            'entidades'        => $entidades,
            'entidadPrincipal' => (int) (optional($entidades->firstWhere('entidad_principal', true))->id ?? 0),
            'membresias'       => Membresia::select('id', 'nombre', 'entidad_id')->get(),
            'inscritos'        => $inscritos,
            'fechaCorte'       => Carbon::parse(config('multievento.fecha_corte', '2026-01-01'))->startOfDay(),
            // Matches evento→actividad recordados de importaciones previas (clave => actividad_id).
            'mapeoGuardado'    => MultieventoMapeo::pluck('actividad_id', 'clave')->map(fn ($v) => (int) $v)->all(),
        ];
    }

    /**
     * Agrupa las filas por evento (solo las que pasan el corte por fecha) y sugiere una
     * actividad para cada uno.
     *
     * @return array<string, array{nombre_evento:string, fecha_evento:string, filas:int, sugerida_id:?int, sugerida_nombre:?string}>
     */
    private function detectarEventos(array $filas, array $ctx): array
    {
        $eventos = [];
        foreach ($filas as $fila) {
            $fechaEvento = $this->parsearFechaEvento($fila['fecha_evento'] ?? '');
            if ($fechaEvento === null || $fechaEvento->lt($ctx['fechaCorte'])) {
                continue; // descartada por fecha: no forma parte del mapeo de eventos
            }
            $nombreEvento = trim((string) ($fila['nombre_evento'] ?? ''));
            $clave = $this->claveEvento($nombreEvento, $fechaEvento);

            if (!isset($eventos[$clave])) {
                // Prioridad: match recordado de importaciones previas; si no, sugerencia automática.
                $guardadaId = $ctx['mapeoGuardado'][$clave] ?? null;
                $actGuardada = $guardadaId ? $ctx['actividades']->firstWhere('id', $guardadaId) : null;

                if ($actGuardada) {
                    $sugId = (int) $actGuardada->id;
                    $sugNombre = (string) $actGuardada->nombre;
                    $origen = 'guardado';
                } else {
                    [$sugId, $sugNombre] = $this->sugerirActividad($nombreEvento, $fechaEvento, $ctx);
                    $origen = $sugId ? 'auto' : null;
                }

                $eventos[$clave] = [
                    'nombre_evento'     => $nombreEvento,
                    'fecha_evento'      => $fechaEvento->toDateString(),
                    'filas'             => 0,
                    'sugerida_id'       => $sugId,
                    'sugerida_nombre'   => $sugNombre,
                    'origen_sugerencia' => $origen,
                ];
            }
            $eventos[$clave]['filas']++;
        }

        return $eventos;
    }

    private function analizarFila(array $fila, array $eventos, array $mapeo, array $ctx, array &$vistos, bool $ejecutar): array
    {
        // 1) Corte por fecha.
        $fechaEvento = $this->parsearFechaEvento($fila['fecha_evento'] ?? '');
        $nombreEvento = trim((string) ($fila['nombre_evento'] ?? ''));
        if ($fechaEvento === null) {
            return $this->fila('descartada_fecha', $nombreEvento, null, null, ['FechaEvento vacía o inválida']);
        }
        if ($fechaEvento->lt($ctx['fechaCorte'])) {
            return $this->fila('descartada_fecha', $nombreEvento, $fechaEvento->toDateString(), null,
                ['FechaEvento anterior al corte (' . $ctx['fechaCorte']->toDateString() . ')']);
        }

        $clave = $this->claveEvento($nombreEvento, $fechaEvento);

        // 2) Actividad destino (mapeo confirmado, o sugerencia en el preview).
        $actividadId = array_key_exists($clave, $mapeo)
            ? (int) $mapeo[$clave]
            : ($ejecutar ? null : ($eventos[$clave]['sugerida_id'] ?? null));

        $actividad = $actividadId ? $ctx['actividades']->firstWhere('id', $actividadId) : null;
        if (!$actividad) {
            return $this->fila('sin_actividad', $nombreEvento, $fechaEvento->toDateString(), null,
                ['Evento sin actividad asignada']);
        }

        // 3) Email obligatorio y válido.
        $email = strtolower(trim((string) ($fila['email'] ?? '')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->fila('error', $nombreEvento, $fechaEvento->toDateString(), $actividad->id, ['Email vacío o inválido']);
        }

        $mensajes = [];
        $nombre = trim((string) ($fila['nombre'] ?? ''));
        $apellido = trim((string) ($fila['apellido'] ?? ''));
        $u = $this->resolverUsuario($email, $nombre, $apellido);
        if ($u['placeholder']) {
            $mensajes[] = 'Email compartido con otra persona; se usa un email placeholder (' . $u['email'] . ')';
        }

        // 4) Dedupe (actividad + usuario resuelto): dentro del archivo y contra la BD.
        $claveDedupe = $actividad->id . '||' . $u['email'];
        if (isset($vistos[$claveDedupe])) {
            return $this->fila('omitir', $nombreEvento, $fechaEvento->toDateString(), $actividad->id,
                ['Fila repetida en el archivo (misma persona en la misma actividad)'], $u['existe'], ['email' => $u['email']]);
        }
        if (isset($ctx['inscritos'][$actividad->id][$u['email']])) {
            return $this->fila('omitir', $nombreEvento, $fechaEvento->toDateString(), $actividad->id,
                ['Ya tiene inscripción a esta actividad'], true, ['email' => $u['email']]);
        }

        // 5) Membresía + entidad de la actividad ruteada.
        $ctxMem = [
            'entidadActividad' => (int) $actividad->entidad_id,
            'entidades'        => $ctx['entidades'],
            'membresias'       => $ctx['membresias'],
            'entidadPrincipal' => $ctx['entidadPrincipal'],
        ];
        [$membresiaId, $membresiaNombre, $msgMem] = $this->resolverMembresia((string) ($fila['membresia'] ?? ''), $ctxMem);
        if ($msgMem) {
            $mensajes[] = $msgMem;
        }

        // 6) Montos y estado de pago.
        $anio = $actividad->fecha_inicio ? Carbon::parse($actividad->fecha_inicio)->year : (int) $fechaEvento->year;
        $pendiente = $this->parsearMonto($fila['pendiente'] ?? '');
        $valor = $this->parsearMonto($fila['valor'] ?? '');
        $fechaPago = $this->parsearFechaPago($fila['fecha_pago'] ?? '', $anio);
        $referencia = trim((string) ($fila['forma'] ?? '')) ?: null;
        $pagoCol = Str::upper(Str::ascii(trim((string) ($fila['pago'] ?? ''))));

        $pagado = $valor > 0 || $fechaPago !== null || $referencia !== null;
        $monto = $pagado && $valor > 0 ? $valor : ($pendiente ?? 0.0);
        if ($pagoCol === 'NO') {
            // "NO" = no aplica: la actividad es sin costo (incluida por membresía).
            $monto = 0.0;
        }
        $saldado = $pagado || $monto <= 0;

        $online = Str::upper(Str::ascii(trim((string) ($fila['modalidad'] ?? '')))) === 'ONLINE';

        $datos = [
            'actividad_id'      => $actividad->id,
            'email'             => $u['email'],
            'name'              => $u['nombre'],
            'apellido'          => $apellido,
            'telefono'          => trim((string) ($fila['celular'] ?? '')) ?: null,
            'whatsapp'          => trim((string) ($fila['telefono_wa'] ?? '')) ?: null,
            'direccion'         => trim((string) ($fila['barrio'] ?? '')) ?: null,
            'medio_comunicacion' => trim((string) ($fila['medio'] ?? '')) ?: null,
            'recibir_info_tk'   => $this->parsearSiNo($fila['recibir_info'] ?? ''),
            'membresia_id'      => $membresiaId,
            'membresia_nombre'  => $membresiaNombre,
            'monto'             => $monto,
            'pago'              => $saldado ? 'Saldado' : 'Pendiente',
            'estado'            => $saldado ? 'Confirmada' : 'Registrada',
            'fecha_pago'        => $fechaPago,
            'referencia_pago'   => $referencia,
            'observaciones'     => $this->armarObservaciones($fila),
            'asistencia'        => $this->parsearAsistencia($fila['asistencia'] ?? ''),
            'confirmado_manual' => $this->parsearBooleano($fila['confirmado_manual'] ?? ''),
            'marca_temporal'    => $this->parsearMarcaTemporal($fila['marca_temporal'] ?? ''),
            'online'            => $online,
        ];

        // Reservar la clave para dedupe intra-archivo.
        $vistos[$claveDedupe] = true;

        return $this->fila('crear', $nombreEvento, $fechaEvento->toDateString(), $actividad->id, $mensajes, $u['existe'], $datos);
    }

    private function crearInscripcion(array $info, array $ctx): void
    {
        $datos = $info['datos'];

        $user = User::where('email', $datos['email'])->first();
        if (!$user) {
            $user = User::create([
                'name'               => $datos['name'],
                'email'              => $datos['email'],
                'password'           => Hash::make($this->generarPassword($datos['apellido'])),
                'telefono'           => $datos['telefono'],
                'whatsapp'           => $datos['whatsapp'],
                'direccion'          => $datos['direccion'],
                'medio_comunicacion' => $datos['medio_comunicacion'],
            ]);
            $user->syncRoles(['asistant']);

            if ($datos['membresia_id'] || $datos['recibir_info_tk']) {
                $user->updateMembresiaUsuario([
                    'membresia_id'                => $datos['membresia_id'],
                    'membresia_inscripcion_fecha' => now()->toDateString(),
                    'info_tarjetas_kadampa'       => $datos['recibir_info_tk'],
                ]);
            }
        }

        $inscripcion = Inscripcion::create([
            'actividad_id'      => $datos['actividad_id'],
            'user_id'           => $user->id,
            'guest_user_id'     => null,
            'membresia'         => $datos['membresia_nombre'],
            'precioGeneral'     => $datos['monto'],
            'montoActividad'    => $datos['monto'],
            'montoGrabacion'    => null,
            'montoTransporte'   => null,
            'montoComidas'      => null,
            'montoapagar'       => $datos['monto'],
            'pago'              => $datos['pago'],
            'fecha_pago'        => $datos['fecha_pago'],
            'referencia_pago'   => $datos['referencia_pago'],
            'observaciones'     => $datos['observaciones'],
            'estado'            => $datos['estado'],
            'envioLinkStream'   => ($datos['online'] ?? false) ? 'Pendiente' : 'No aplica',
            'envioRegistro'     => 'Pendiente',
            'envioConfirmacion' => 'Pendiente',
            'envioGrabacion'    => 'No aplica',
            'asistencia'        => $datos['asistencia'],
            'confirmado_manual' => $datos['confirmado_manual'],
            'online'            => $datos['online'] ?? false,
        ]);

        // Conservar la fecha de inscripción original (Marca temporal del CSV).
        if ($datos['marca_temporal']) {
            $inscripcion->created_at = $datos['marca_temporal'];
            $inscripcion->save();
        }

        // Registrar el email resuelto como ya inscripto (dedupe en la misma corrida).
        $ctx['inscritos'][$datos['actividad_id']][$datos['email']] = true;
    }

    /**
     * Resuelve el usuario de una fila.
     *
     * @return array{email:string, existe:bool, nombre:string, placeholder:bool}
     */
    private function resolverUsuario(string $email, string $nombre, string $apellido): array
    {
        $emailLower = strtolower(trim($email));
        $nombreCompleto = trim($nombre . ' ' . $apellido);
        if ($nombreCompleto === '') {
            $nombreCompleto = $emailLower;
        }

        $nombreNorm = $this->normalizarNombre($nombreCompleto);
        $dbUser = User::where('email', $emailLower)->first();

        // ¿Quién es el "dueño" del email real? La BD manda; si no, la primera fila de esta corrida.
        $ownerNombre = $dbUser ? $this->normalizarNombre($dbUser->name) : ($this->emailReal[$emailLower] ?? null);

        if ($ownerNombre === null) {
            // Nadie reclamó este email todavía: lo toma esta persona (email real).
            $this->emailReal[$emailLower] = $nombreNorm;

            return ['email' => $emailLower, 'existe' => false, 'nombre' => $nombreCompleto, 'placeholder' => false];
        }

        // Mismo email + mismo nombre => misma persona.
        if ($ownerNombre === $nombreNorm) {
            return ['email' => $emailLower, 'existe' => (bool) $dbUser, 'nombre' => $nombreCompleto, 'placeholder' => false];
        }

        // Persona distinta que comparte el email => usuario nuevo con email placeholder determinístico.
        $placeholder = $this->emailPlaceholder($emailLower, $nombre, $apellido);
        $phUser = User::where('email', $placeholder)->first();

        return ['email' => $placeholder, 'existe' => (bool) $phUser, 'nombre' => $nombreCompleto, 'placeholder' => true];
    }

    private function emailPlaceholder(string $emailReal, string $nombre, string $apellido): string
    {
        $local = Str::before($emailReal, '@');
        $partes = array_values(array_filter([
            $local,
            'i',
            $this->slug($nombre),
            $this->slug($apellido),
        ], fn ($p) => $p !== ''));

        return implode('.', $partes) . '@' . self::DOMINIO_PLACEHOLDER;
    }

    private function slug(string $s): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(Str::ascii($s))), '-');
    }

    private function normalizarNombre(string $s): string
    {
        return trim(preg_replace('/\s+/', ' ', strtolower(Str::ascii($s))));
    }

    private function armarObservaciones(array $fila): ?string
    {
        $partes = ['Importado de planilla multievento'];
        $comentarios = trim((string) ($fila['comentarios'] ?? ''));
        if ($comentarios !== '') {
            $partes[] = 'Comentarios: ' . $comentarios;
        }

        return implode('. ', $partes);
    }

    private function parsearAsistencia(?string $valor): string
    {
        $v = strtolower(Str::ascii(trim((string) $valor)));

        return in_array($v, ['1', 'si', 'x', 'presente', 'true'], true) ? 'Presente' : 'Pendiente';
    }

    private function parsearBooleano(?string $valor): bool
    {
        $v = strtolower(Str::ascii(trim((string) $valor)));

        return in_array($v, ['1', 'si', 'x', 'true', 'verdadero'], true);
    }

    private function parsearFechaEvento(?string $valor): ?Carbon
    {
        $v = trim((string) $valor);
        if ($v === '') {
            return null;
        }
        $partes = preg_split('/[\/\-]/', $v);
        if (count($partes) < 3 || !is_numeric($partes[0]) || !is_numeric($partes[1]) || !is_numeric($partes[2])) {
            return null;
        }
        $dia = (int) $partes[0];
        $mes = (int) $partes[1];
        $anio = (int) $partes[2];
        if ($anio < 100) {
            $anio += 2000;
        }
        try {
            return Carbon::create($anio, $mes, $dia)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function claveEvento(string $nombreEvento, Carbon $fechaEvento): string
    {
        return $nombreEvento . '||' . $fechaEvento->toDateString();
    }

    /**
     * Persiste el match evento→actividad confirmado para prellenarlo en futuras importaciones.
     * Se guarda la asignación efectiva de cada evento (mapeo confirmado o, si no, la sugerencia).
     */
    private function guardarMapeos(array $eventos, array $mapeo, array $ctx): void
    {
        foreach ($eventos as $clave => $ev) {
            $actividadId = array_key_exists($clave, $mapeo) ? (int) $mapeo[$clave] : $ev['sugerida_id'];
            if (!$actividadId || !$ctx['actividades']->firstWhere('id', $actividadId)) {
                continue;
            }

            MultieventoMapeo::updateOrCreate(
                ['clave' => $clave],
                [
                    'nombre_evento' => $ev['nombre_evento'],
                    'fecha_evento'  => $ev['fecha_evento'],
                    'actividad_id'  => $actividadId,
                ],
            );
        }
    }

    /**
     * Sugiere una actividad para un evento: match del texto entre comillas de NombreEvento
     * (o el nombre completo) contra actividades.nombre, reforzado por la fecha.
     *
     * @return array{0: ?int, 1: ?string}
     */
    private function sugerirActividad(string $nombreEvento, Carbon $fechaEvento, array $ctx): array
    {
        $titulo = preg_match('/"([^"]+)"/u', $nombreEvento, $m) ? $m[1] : $nombreEvento;
        $tituloNorm = $this->normalizarHeader($titulo);
        $eventoNorm = $this->normalizarHeader($nombreEvento);
        $fecha = $fechaEvento->toDateString();

        $porNombre = null;
        foreach ($ctx['actividades'] as $a) {
            $nombreNorm = $this->normalizarHeader((string) $a->nombre);
            if ($nombreNorm === '') {
                continue;
            }
            $coincideNombre = $tituloNorm !== '' && (
                $nombreNorm === $tituloNorm
                || str_contains($nombreNorm, $tituloNorm)
                || str_contains($tituloNorm, $nombreNorm)
                || str_contains($eventoNorm, $nombreNorm)
            );
            if (!$coincideNombre) {
                continue;
            }
            $coincideFecha = $a->fecha_inicio && Carbon::parse($a->fecha_inicio)->toDateString() === $fecha;
            if ($coincideFecha) {
                return [(int) $a->id, (string) $a->nombre]; // match fuerte (nombre + fecha)
            }
            $porNombre ??= $a; // primer match por nombre (sin fecha)
        }

        return $porNombre ? [(int) $porNombre->id, (string) $porNombre->nombre] : [null, null];
    }

    /**
     * @param  array<string,mixed>|null  $datos
     */
    private function fila(string $accion, string $nombreEvento, ?string $fechaEvento, ?int $actividadId, array $mensajes, bool $userExiste = false, ?array $datos = null): array
    {
        return [
            'accion'        => $accion,
            'nombre_evento' => $nombreEvento,
            'fecha_evento'  => $fechaEvento,
            'actividad_id'  => $actividadId,
            'datos'         => $datos,
            'user_existe'   => $userExiste,
            'mensajes'      => $mensajes,
        ];
    }
}

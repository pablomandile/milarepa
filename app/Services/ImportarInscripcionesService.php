<?php

namespace App\Services;

use App\Models\Actividad;
use App\Models\Entidad;
use App\Models\Inscripcion;
use App\Models\Membresia;
use App\Models\User;
use App\Services\Concerns\ParseaCsvInscripciones;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Importa inscripciones legacy (CSV de Google Forms) a una Actividad.
 *
 * - Matchea/crea el usuario por email (col "Dirección de correo electrónico").
 * - Es idempotente: dedupe por email contra las inscripciones existentes de la
 *   actividad y contra los emails ya vistos en el CSV → se puede re-subir el
 *   archivo entero y solo entran los nuevos.
 */
class ImportarInscripcionesService
{
    use ParseaCsvInscripciones;

    /** Mapeo: clave interna => header esperado (se compara normalizado). */
    private const COLUMNAS = [
        'marca_temporal' => 'Marca temporal',
        'modalidad_participacion' => '¿Bajo que modalidad vas a participar?',
        'email_tk_online' => 'Dirección de correo electrónico TK (ONLINE)',
        'tiene_tk'       => '¿TENÉS ALGUNA DE NUESTRAS TARJETAS KADAMPA?',
        'email_tk'       => 'Dirección de correo electrónico TK',
        'nombre'         => 'Nombre',
        'apellido'       => 'Apellido',
        'email'          => 'Dirección de correo electrónico',
        'barrio'         => '¿De qué barrio/provincia sos?',
        'telefono'       => 'Teléfono celular',
        'como_entero'    => '¿Cómo te enteraste de este evento?',
        'recibir_info'   => '¿TE GUSTARÍA RECIBIR MÁS INFORMACIÓN PARA OBTENER TU TARJETA KADAMPA?',
        'pendiente'      => 'Pendiente',
        'respuesta'      => 'Respuesta',
        'envio_info_tk'  => 'Envio Info TKS',
        'membresia'      => 'Membresía',
        'fecha_pago'     => 'FechaPago',
        'valor'          => 'Valor',
        'forma'          => 'Forma',
        'asistencia'     => 'Asistencia',
    ];

    /**
     * Headers conocidos del formato que NO mapeamos a ningún campo (se ignoran a
     * propósito). No se reportan como "desconocidos". Cualquier otro header que
     * no esté acá ni en COLUMNAS se reporta como columna desconocida.
     */
    private const COLUMNAS_IGNORADAS = [
        'Confirmá tu dirección de correo electrónico',
        'Links Youtube',
        'Pago',
        'Recordatorio pago',
        'Confirmación',
        'NombreEvento',
        'DatosEvento',
        'Programa',
    ];

    public function previsualizar(UploadedFile $archivo, int $actividadId): array
    {
        return $this->procesar($archivo, $actividadId, false);
    }

    public function importar(UploadedFile $archivo, int $actividadId): array
    {
        return $this->procesar($archivo, $actividadId, true);
    }

    private function procesar(UploadedFile $archivo, int $actividadId, bool $ejecutar): array
    {
        $ctx = $this->contexto($actividadId);
        [$filas, $columnasDesconocidas] = $this->parsearCsv($archivo);

        $emailsVistos = [];
        $resultado = [
            'total_filas'           => count($filas),
            'a_crear'               => 0,
            'usuarios_nuevos'       => 0,
            'usuarios_existentes'   => 0,
            'omitidas'              => 0,
            'errores'               => 0,
            'creadas'               => 0,
            'columnas_desconocidas' => $columnasDesconocidas,
            'filas'                 => [],
        ];

        $run = function () use ($filas, $ctx, &$emailsVistos, &$resultado, $ejecutar) {
            foreach ($filas as $index => $fila) {
                $info = $this->analizarFila($fila, $emailsVistos, $ctx);
                $info['linea'] = $index + 2; // +1 header, +1 base-1
                $resultado['filas'][] = $info;

                if ($info['accion'] === 'error') {
                    $resultado['errores']++;
                    continue;
                }
                if ($info['accion'] === 'omitir') {
                    $resultado['omitidas']++;
                    continue;
                }

                // accion === 'crear'
                $emailsVistos[$info['datos']['email']] = true;
                $resultado['a_crear']++;
                $info['user_existe'] ? $resultado['usuarios_existentes']++ : $resultado['usuarios_nuevos']++;

                if ($ejecutar) {
                    $this->crearInscripcion($info, $ctx);
                    $resultado['creadas']++;
                }
            }
        };

        if ($ejecutar) {
            DB::transaction($run);
        } else {
            $run();
        }

        return $resultado;
    }

    /** Carga datos de referencia una sola vez. */
    private function contexto(int $actividadId): array
    {
        $actividad = Actividad::findOrFail($actividadId);

        $emailsInscriptos = Inscripcion::query()
            ->where('actividad_id', $actividadId)
            ->with(['user:id,email', 'guestUser:id,email'])
            ->get()
            ->flatMap(fn ($i) => [optional($i->user)->email, optional($i->guestUser)->email])
            ->filter()
            ->map(fn ($e) => strtolower(trim($e)))
            ->flip();

        $entidades = Entidad::select('id', 'nombre', 'abreviacion', 'entidad_principal')->get();

        return [
            'actividad'         => $actividad,
            'anio'              => $actividad->fecha_inicio ? Carbon::parse($actividad->fecha_inicio)->year : (int) date('Y'),
            'entidadActividad'  => (int) $actividad->entidad_id,
            'membresias'        => Membresia::select('id', 'nombre', 'entidad_id')->get(),
            'entidades'         => $entidades,
            'entidadPrincipal'  => (int) (optional($entidades->firstWhere('entidad_principal', true))->id ?? 0),
            'emailsInscriptos'  => $emailsInscriptos,
        ];
    }

    private function analizarFila(array $fila, array $emailsVistos, array $ctx): array
    {
        $mensajes = [];

        $nombre = trim((string) ($fila['nombre'] ?? ''));
        $apellido = trim((string) ($fila['apellido'] ?? ''));
        $email = strtolower(trim((string) ($fila['email'] ?? '')));
        if ($email === '') {
            $email = strtolower(trim((string) ($fila['email_tk'] ?? '')));
        }

        // Email obligatorio y válido.
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->fila('error', null, false, ['Email vacío o inválido']);
        }

        // Dedupe dentro del CSV.
        if (isset($emailsVistos[$email])) {
            return $this->fila('omitir', ['email' => $email], false, ['Email repetido en el CSV (fila anterior)']);
        }

        // Dedupe contra inscripciones ya existentes de la actividad.
        if (isset($ctx['emailsInscriptos'][$email])) {
            return $this->fila('omitir', ['email' => $email], true, ['Ya tiene inscripción a esta actividad']);
        }

        $nombreCompleto = trim($nombre . ' ' . $apellido);
        if ($nombreCompleto === '') {
            $nombreCompleto = $email;
            $mensajes[] = 'Nombre y apellido vacíos; se usó el email como nombre';
        }
        if (Str::contains(Str::lower($nombreCompleto . ' ' . $email), 'prueba')) {
            $mensajes[] = 'Posible fila de prueba: revisar antes de confirmar';
        }

        $userExistente = User::where('email', $email)->first();

        // Modalidad de participación. Si es ONLINE, lo esperado es que el usuario tenga
        // membresía online (membresia_usuario.membresia_online). El email TK (ONLINE)
        // indica bajo qué cuenta está esa membresía; si viene vacío, se usa el principal.
        // Si NO tiene membresía online igual se inscribe, pero se deja constancia en
        // las observaciones (y como aviso en la previsualización).
        $modalidad = Str::upper(Str::ascii(trim((string) ($fila['modalidad_participacion'] ?? ''))));
        $online = $modalidad === 'ONLINE';
        $emailTkOnline = strtolower(trim((string) ($fila['email_tk_online'] ?? '')));
        $avisoSinOnline = null;

        if ($online) {
            $emailMembresia = $emailTkOnline !== '' ? $emailTkOnline : $email;
            $userOnline = ($emailMembresia === $email)
                ? $userExistente
                : User::where('email', $emailMembresia)->first();
            $tieneMembresiaOnline = $userOnline && (bool) $userOnline->membresia_online;
            if (!$tieneMembresiaOnline) {
                $avisoSinOnline = "Modalidad ONLINE sin membresía online (verificado por $emailMembresia)";
                $mensajes[] = $avisoSinOnline . '; se inscribe igual y se reporta en observaciones';
            }
        }

        // Membresía + entidad.
        [$membresiaId, $membresiaNombre, $msgMem] = $this->resolverMembresia((string) ($fila['membresia'] ?? ''), $ctx);
        if ($msgMem) {
            $mensajes[] = $msgMem;
        }

        // Montos y estado de pago.
        $pendiente = $this->parsearMonto($fila['pendiente'] ?? '');
        $valor = $this->parsearMonto($fila['valor'] ?? '');
        $fechaPago = $this->parsearFechaPago($fila['fecha_pago'] ?? '', $ctx['anio']);
        $referencia = trim((string) ($fila['forma'] ?? '')) ?: null;

        $pagado = $valor > 0 || $fechaPago !== null || $referencia !== null;
        $monto = $pagado && $valor > 0 ? $valor : ($pendiente ?? 0.0);
        // Si no hay nada que pagar (inscripción/actividad gratuita) queda 'Saldado',
        // aunque no haya registro de pago.
        $saldado = $pagado || $monto <= 0;

        $datos = [
            'email'            => $email,
            'name'             => $nombreCompleto,
            'apellido'         => $apellido,
            'telefono'         => trim((string) ($fila['telefono'] ?? '')) ?: null,
            'direccion'        => trim((string) ($fila['barrio'] ?? '')) ?: null,
            'recibir_info_tk'  => $this->parsearSiNo($fila['recibir_info'] ?? ''),
            'membresia_id'     => $membresiaId,
            'membresia_nombre' => $membresiaNombre,
            'monto'            => $monto,
            'pago'             => $saldado ? 'Saldado' : 'Pendiente',
            'estado'           => $saldado ? 'Confirmada' : 'Registrada',
            'fecha_pago'       => $fechaPago,
            'referencia_pago'  => $referencia,
            'envio_registro'   => Str::contains(Str::upper((string) ($fila['respuesta'] ?? '')), 'ENVIADA') ? 'Enviada' : 'Pendiente',
            'observaciones'    => $this->armarObservaciones($fila, $avisoSinOnline),
            'marca_temporal'   => $this->parsearMarcaTemporal($fila['marca_temporal'] ?? ''),
            'online'           => $online,
            'modalidad'        => $modalidad !== '' ? ($online ? 'Online' : 'Presencial') : null,
        ];

        return $this->fila('crear', $datos, (bool) $userExistente, $mensajes);
    }

    private function crearInscripcion(array $info, array $ctx): void
    {
        $datos = $info['datos'];

        $user = User::where('email', $datos['email'])->first();
        if (!$user) {
            $user = User::create([
                'name'      => $datos['name'],
                'email'     => $datos['email'],
                'password'  => Hash::make($this->generarPassword($datos['apellido'])),
                'telefono'  => $datos['telefono'],
                'direccion' => $datos['direccion'],
            ]);
            $user->syncRoles(['asistant']);

            // Membresía solo para usuarios nuevos (no se pisa la de existentes).
            if ($datos['membresia_id'] || $datos['recibir_info_tk']) {
                $user->updateMembresiaUsuario([
                    'membresia_id'                => $datos['membresia_id'],
                    'membresia_inscripcion_fecha' => now()->toDateString(),
                    'info_tarjetas_kadampa'       => $datos['recibir_info_tk'],
                ]);
            }
        }

        $inscripcion = Inscripcion::create([
            'actividad_id'      => $ctx['actividad']->id,
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
            // Online: queda 'Pendiente' para poder enviar el link de stream. Presencial: 'No aplica'.
            'envioLinkStream'   => ($datos['online'] ?? false) ? 'Pendiente' : 'No aplica',
            'envioRegistro'     => $datos['envio_registro'],
            // Queda 'Pendiente' a propósito: el envío de confirmaciones se probará luego.
            'envioConfirmacion' => 'Pendiente',
            'envioGrabacion'    => 'No aplica',
            'asistencia'        => 'Pendiente',
            'online'            => $datos['online'] ?? false,
        ]);

        // Conservar la fecha de inscripción original (Marca temporal del CSV).
        if ($datos['marca_temporal']) {
            $inscripcion->created_at = $datos['marca_temporal'];
            $inscripcion->save();
        }
    }

    private function fila(string $accion, ?array $datos, bool $userExiste, array $mensajes): array
    {
        return [
            'accion'      => $accion,
            'datos'       => $datos,
            'user_existe' => $userExiste,
            'mensajes'    => $mensajes,
        ];
    }

    private function armarObservaciones(array $fila, ?string $extra = null): ?string
    {
        $partes = ['Importado de CSV'];
        $modalidad = trim((string) ($fila['modalidad_participacion'] ?? ''));
        if ($modalidad !== '') {
            $partes[] = 'Modalidad: ' . $modalidad;
        }
        $tkOnline = trim((string) ($fila['email_tk_online'] ?? ''));
        if ($tkOnline !== '') {
            $partes[] = 'TK Online: ' . $tkOnline;
        }
        $como = trim((string) ($fila['como_entero'] ?? ''));
        if ($como !== '') {
            $partes[] = 'Cómo se enteró: ' . $como;
        }
        if ($extra !== null && $extra !== '') {
            $partes[] = $extra;
        }
        return implode('. ', $partes);
    }

    /**
     * @return array{0: array<int, array<string, string>>, 1: array<int, string>}
     *               [filas mapeadas, headers desconocidos (texto crudo)]
     */
    private function parsearCsv(UploadedFile $archivo): array
    {
        $contenido = file_get_contents($archivo->getRealPath());

        return $this->mapearCsv($contenido, self::COLUMNAS, self::COLUMNAS_IGNORADAS);
    }
}

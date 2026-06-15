<?php

namespace App\Services;

use App\Models\Actividad;
use App\Models\Entidad;
use App\Models\Inscripcion;
use App\Models\Membresia;
use App\Models\User;
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

    /**
     * "TK CORAZÓN CMKA" / "SIN MEMBRESIA" → [membresia_id|null, nombre snapshot, mensaje|null]
     */
    private function resolverMembresia(string $raw, array $ctx): array
    {
        $up = Str::upper(Str::ascii(trim($raw)));
        if ($up === '' || Str::contains($up, 'SIN MEMBRESIA')) {
            return [null, 'Sin membresía', null];
        }

        // Entidad (token al final): CMKA o Nagaryhuna; default = entidad de la actividad.
        $entidadId = $ctx['entidadActividad'];
        if (Str::contains($up, 'NAGAR')) {
            $entidadId = optional($ctx['entidades']->first(fn ($e) => Str::contains(Str::upper(Str::ascii($e->nombre)), 'NAGAR')))->id ?? $entidadId;
        } elseif (Str::contains($up, 'CMKA')) {
            $entidadId = optional($ctx['entidades']->first(fn ($e) => Str::upper((string) $e->abreviacion) === 'CMKA'))->id ?? $entidadId;
        }

        $tipo = null;
        if (Str::contains($up, 'CORAZON')) {
            $tipo = 'corazon';
        } elseif (Str::contains($up, 'BENEFACTOR')) {
            $tipo = 'benefactor';
        } elseif (Str::contains($up, 'CLASE')) {
            $tipo = 'clases';
        }

        if ($tipo) {
            $membresia = $this->buscarMembresia($ctx, $entidadId, $tipo);

            // Fallback: si la entidad resuelta no tiene esa membresía, usar la entidad principal.
            $mapeadaAPrincipal = false;
            if (!$membresia && $ctx['entidadPrincipal'] && $ctx['entidadPrincipal'] !== (int) $entidadId) {
                $membresia = $this->buscarMembresia($ctx, $ctx['entidadPrincipal'], $tipo);
                $mapeadaAPrincipal = (bool) $membresia;
            }

            if ($membresia) {
                $mensaje = $mapeadaAPrincipal
                    ? "Membresía '" . trim($raw) . "' mapeada a la entidad principal ({$membresia->nombre})"
                    : null;
                return [$membresia->id, $membresia->nombre, $mensaje];
            }
        }

        return [null, trim($raw), "No se pudo mapear la membresía '" . trim($raw) . "'; se guarda como texto sin asociar"];
    }

    /** Busca una membresía por entidad + tipo (corazon/benefactor/clases). */
    private function buscarMembresia(array $ctx, int $entidadId, string $tipo)
    {
        return $ctx['membresias']->first(function ($m) use ($entidadId, $tipo) {
            return (int) $m->entidad_id === $entidadId
                && Str::contains(Str::lower(Str::ascii($m->nombre)), $tipo);
        });
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

    private function parsearMonto(?string $valor): ?float
    {
        $digitos = preg_replace('/[^0-9]/', '', (string) $valor);
        return $digitos === '' ? null : (float) $digitos;
    }

    private function parsearFechaPago(?string $valor, int $anio): ?string
    {
        $v = trim((string) $valor);
        if ($v === '') {
            return null;
        }
        // Formato esperado "d/m" (a veces "d/m/Y").
        $partes = preg_split('/[\/\-]/', $v);
        if (count($partes) < 2 || !is_numeric($partes[0]) || !is_numeric($partes[1])) {
            return null;
        }
        $dia = (int) $partes[0];
        $mes = (int) $partes[1];
        $anioFinal = isset($partes[2]) && is_numeric($partes[2]) ? (int) $partes[2] : $anio;
        if ($anioFinal < 100) {
            $anioFinal += 2000;
        }
        try {
            return Carbon::create($anioFinal, $mes, $dia)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function parsearMarcaTemporal(?string $valor): ?string
    {
        $v = trim((string) $valor);
        if ($v === '') {
            return null;
        }
        foreach (['d/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y', 'd-m-Y H:i:s'] as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $v)->toDateTimeString();
            } catch (\Throwable $e) {
                // probar siguiente formato
            }
        }
        return null;
    }

    private function parsearSiNo(?string $valor): bool
    {
        $v = Str::upper(Str::ascii(trim((string) $valor)));
        return Str::startsWith($v, 'SI');
    }

    private function generarPassword(string $apellido): string
    {
        $base = $apellido !== '' ? $apellido : 'asistente';
        $base = preg_replace('/[^A-Za-z0-9]/', '', Str::ascii($base));
        return ($base === '' ? 'asistente' : $base) . '2026';
    }

    /**
     * @return array{0: array<int, array<string, string>>, 1: array<int, string>}
     *               [filas mapeadas, headers desconocidos (texto crudo)]
     */
    private function parsearCsv(UploadedFile $archivo): array
    {
        $contenido = file_get_contents($archivo->getRealPath());

        $encoding = mb_detect_encoding($contenido, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $contenido = mb_convert_encoding($contenido, 'UTF-8', $encoding);
        }
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);

        // str_getcsv respeta comillas y saltos de línea dentro de celdas, así que
        // parseamos el contenido completo en filas con un lector que soporta multilínea.
        $filasCrudas = $this->leerFilasCsv($contenido);
        if (empty($filasCrudas)) {
            return [[], []];
        }

        $headerCrudo = $filasCrudas[0];
        $header = array_map(fn ($h) => $this->normalizar($h), $headerCrudo);

        $mapeo = [];
        foreach (self::COLUMNAS as $clave => $etiqueta) {
            $pos = array_search($this->normalizar($etiqueta), $header, true);
            $mapeo[$clave] = $pos !== false ? $pos : null;
        }

        // Columnas desconocidas: headers no mapeados ni en la lista de ignorados conocidos.
        $conocidas = [];
        foreach (array_merge(array_values(self::COLUMNAS), self::COLUMNAS_IGNORADAS) as $etiqueta) {
            $conocidas[$this->normalizar($etiqueta)] = true;
        }
        $desconocidas = [];
        foreach ($headerCrudo as $h) {
            $norm = $this->normalizar((string) $h);
            if ($norm !== '' && !isset($conocidas[$norm])) {
                $desconocidas[] = trim((string) $h);
            }
        }

        $filas = [];
        for ($i = 1; $i < count($filasCrudas); $i++) {
            $celdas = $filasCrudas[$i];
            $fila = [];
            foreach ($mapeo as $clave => $pos) {
                $fila[$clave] = $pos !== null && isset($celdas[$pos]) ? trim((string) $celdas[$pos]) : '';
            }
            $filas[] = $fila;
        }

        return [$filas, array_values(array_unique($desconocidas))];
    }

    /** Lee CSV con soporte de comillas y celdas multilínea (DatosEvento/Programa traen HTML con saltos). */
    private function leerFilasCsv(string $contenido): array
    {
        $delimitador = (substr_count(strtok($contenido, "\n"), ';') > substr_count(strtok($contenido, "\n"), ',')) ? ';' : ',';

        $fp = fopen('php://temp', 'r+');
        fwrite($fp, $contenido);
        rewind($fp);

        $filas = [];
        while (($celdas = fgetcsv($fp, 0, $delimitador)) !== false) {
            // Saltar líneas totalmente vacías.
            if (count($celdas) === 1 && trim((string) $celdas[0]) === '') {
                continue;
            }
            $filas[] = $celdas;
        }
        fclose($fp);

        return $filas;
    }

    private function normalizar(string $h): string
    {
        $h = Str::ascii($h);
        $h = strtolower($h);
        $h = preg_replace('/[^a-z0-9]+/', ' ', $h);
        return trim($h);
    }
}

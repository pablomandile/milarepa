<?php

namespace App\Services;

use App\Models\EstadoCuentaMembresia;
use App\Models\Membresia;
use App\Models\ProgramaEstudio;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ImportarMembresiasService
{
    /**
     * Máximo que admite la columna estado_cuenta_membresias.importe (decimal(10,2)).
     * Un importe mayor casi siempre es un error de tipeo en el CSV: no se registra el
     * pago y se avisa, en lugar de dejar que la BD tire un error de rango (500).
     */
    private const IMPORTE_MAX = 99999999.99;

    /**
     * Columnas esperadas en el CSV (en este orden o por header).
     * Las claves son los nombres internos que usamos en el código.
     */
    private const COLUMNAS = [
        'nombre' => 'Nombre',
        'apellido' => 'Apellido',
        'activo' => 'Activo',
        'tk' => 'TK',
        'programa' => 'Programa',
        'online' => 'ONLINE',
        'mail' => 'Mail',
        'telefonos' => 'Teléfonos',
        'ciudad' => 'Ciudad',
        'newsletter' => 'Newsletter',
        // Pago del mes en curso (columnas opcionales al final del CSV).
        'imp' => 'Imp.',
        'dia' => 'Día',
        'modo' => 'Modo',
        'nota' => 'Nota',
    ];

    public function __construct(private CobroService $cobros)
    {
    }

    /**
     * Parsea el CSV y devuelve un preview sin tocar la BD.
     * Estructura del resultado:
     * [
     *   'total_filas' => int,
     *   'filas_validas' => int,
     *   'usuarios_nuevos' => int,
     *   'usuarios_existentes' => int,
     *   'membresias_a_asignar' => int,
     *   'errores' => int,
     *   'filas' => [['linea' => N, 'datos' => [...], 'accion' => '...', 'mensajes' => [...]], ...]
     * ]
     */
    public function previsualizar(UploadedFile $archivo, int $entidadId, ?string $mesPagado = null): array
    {
        $mesPagado ??= now()->format('Y-m');
        $filas = $this->parsearCsv($archivo);

        $emailsVistos = [];
        $resultado = [
            'total_filas' => count($filas),
            'filas_validas' => 0,
            'usuarios_nuevos' => 0,
            'usuarios_existentes' => 0,
            'sin_cambios' => 0,
            'membresias_a_asignar' => 0,
            'pagos_a_registrar' => 0,
            'errores' => 0,
            'filas' => [],
        ];

        foreach ($filas as $index => $fila) {
            $linea = $index + 2; // +1 por el header, +1 por base 1
            $info = $this->analizarFila($fila, $emailsVistos, $entidadId, $mesPagado);
            $info['linea'] = $linea;

            $resultado['filas'][] = $info;

            if ($info['accion'] === 'error') {
                $resultado['errores']++;
                continue;
            }

            $resultado['filas_validas']++;
            $registraPago = $info['pago']['registrar'] ?? false;
            if (!$info['user_existe']) {
                $resultado['usuarios_nuevos']++;
            } elseif ($info['sin_cambios'] && !$registraPago) {
                $resultado['sin_cambios']++;
            } else {
                $resultado['usuarios_existentes']++;
            }
            if ($info['asignara_membresia']) {
                $resultado['membresias_a_asignar']++;
            }
            if ($registraPago) {
                $resultado['pagos_a_registrar']++;
            }

            $emailsVistos[strtolower($info['datos']['mail'])] = true;
        }

        return $resultado;
    }

    /**
     * Ejecuta la importación. Devuelve los mismos contadores que previsualizar
     * pero con la operación efectiva ya aplicada.
     */
    public function importar(UploadedFile $archivo, int $entidadId, ?string $mesPagado = null): array
    {
        $mesPagado ??= now()->format('Y-m');
        $filas = $this->parsearCsv($archivo);
        $emailsVistos = [];
        $resumen = [
            'total_filas' => count($filas),
            'creados' => 0,
            'actualizados' => 0,
            'sin_cambios' => 0,
            'membresias_asignadas' => 0,
            'pagos_registrados' => 0,
            'errores' => 0,
            'detalle' => [],
        ];

        DB::transaction(function () use ($filas, $entidadId, $mesPagado, &$emailsVistos, &$resumen) {
            foreach ($filas as $index => $fila) {
                $linea = $index + 2;
                $info = $this->analizarFila($fila, $emailsVistos, $entidadId, $mesPagado);

                if ($info['accion'] === 'error') {
                    $resumen['errores']++;
                    $resumen['detalle'][] = [
                        'linea' => $linea,
                        'resultado' => 'error',
                        'mensajes' => $info['mensajes'],
                    ];
                    continue;
                }

                $emailLower = strtolower($info['datos']['mail']);
                $emailsVistos[$emailLower] = true;

                $user = User::where('email', $info['datos']['mail'])->first();
                $esNuevo = !$user;
                $registraPago = $info['pago']['registrar'] ?? false;

                // Usuario existente sin ningún cambio NI pago a registrar: se omite (no se toca la BD).
                if (!$esNuevo && $info['sin_cambios'] && !$registraPago) {
                    $resumen['sin_cambios']++;
                    $resumen['detalle'][] = [
                        'linea' => $linea,
                        'resultado' => 'sin_cambios',
                        'email' => $info['datos']['mail'],
                        'mensajes' => $info['mensajes'],
                    ];
                    continue;
                }

                $datosUser = [
                    'name' => $info['datos']['name'],
                    'telefono' => $info['datos']['telefono'],
                    'direccion' => $info['datos']['ciudad'],
                    'msgxmail' => $info['datos']['newsletter'],
                ];
                if ($info['programa_estudio_id']) {
                    $datosUser['programa_estudio_id'] = $info['programa_estudio_id'];
                    $datosUser['programa_a_distancia'] = (bool) ($info['programa_a_distancia'] ?? false);
                }

                if ($esNuevo) {
                    $datosUser['email'] = $info['datos']['mail'];
                    $datosUser['password'] = Hash::make($info['password_propuesta']);
                    $user = User::create($datosUser);
                    $user->syncRoles(['asistant']);
                    $resumen['creados']++;
                } else {
                    // Solo escribir los campos de usuario si efectivamente cambiaron.
                    if ($info['cambia_usuario']) {
                        $user->update($datosUser);
                    }
                    $resumen['actualizados']++;
                }

                // La membresía se escribe solo si corresponde (nuevo con TK, o existente con cambios).
                if ($info['asignara_membresia']) {
                    $user->updateMembresiaUsuario([
                        'membresia_id' => $info['membresia_id'],
                        'suscripcion' => (bool) ($info['suscripcion'] ?? false),
                        'membresia_inscripcion_fecha' => $user->membresia_inscripcion_fecha ?? now()->toDateString(),
                        'membresia_online' => $info['datos']['online'],
                        'membresia_online_motivo' => $user->membresia_online_motivo ?? null,
                        'info_tarjetas_kadampa' => (bool) ($user->info_tarjetas_kadampa ?? false),
                        'envioInfoTk' => $user->envioInfoTk ?? null,
                    ]);
                    $resumen['membresias_asignadas']++;
                }

                // Pago del mes en curso (estado de cuenta), idempotente por user+membresía+mes.
                if ($registraPago) {
                    $pago = $info['pago'];
                    $cuota = EstadoCuentaMembresia::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'membresia_id' => $info['membresia_id'],
                            'mes_pagado' => $pago['mes_pagado'],
                        ],
                        [
                            'fecha_pago' => $pago['fecha_pago'],
                            'importe' => $pago['importe'],
                            'observaciones' => $pago['observaciones'],
                            'info_pago' => $pago['info_pago'],
                            'pagado' => $pago['pagado'],
                            'estado' => EstadoCuentaMembresia::ESTADO_ACTIVA,
                            'modo' => $pago['modo'],
                        ]
                    );
                    $this->cobros->sincronizarMembresia($cuota);
                    $resumen['pagos_registrados']++;
                }

                $resumen['detalle'][] = [
                    'linea' => $linea,
                    'resultado' => $esNuevo ? 'creado' : 'actualizado',
                    'email' => $info['datos']['mail'],
                    'pago_registrado' => $registraPago,
                    'mensajes' => $info['mensajes'],
                ];
            }
        });

        return $resumen;
    }

    /**
     * Lee el archivo CSV, detecta encoding y devuelve un array asociativo por fila.
     */
    private function parsearCsv(UploadedFile $archivo): array
    {
        $contenido = file_get_contents($archivo->getRealPath());

        // Detectar y convertir encoding a UTF-8 si hace falta.
        $encoding = mb_detect_encoding($contenido, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $contenido = mb_convert_encoding($contenido, 'UTF-8', $encoding);
        }

        // Remover BOM si existe.
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);

        $lineas = preg_split('/\r\n|\n|\r/', $contenido);
        $lineas = array_values(array_filter($lineas, fn ($l) => trim($l) !== ''));

        if (empty($lineas)) {
            return [];
        }

        // Detectar el delimitador (coma o punto y coma).
        $delimitador = (substr_count($lineas[0], ';') > substr_count($lineas[0], ',')) ? ';' : ',';

        // Una línea "tiene contenido" si al parsearla queda al menos una celda no vacía.
        // Descarta filas como ",,,,," que algunos exports (Google Sheets) ponen antes del
        // encabezado o entre datos; si no, esa fila se tomaría como header y todo fallaría.
        $tieneContenido = function (string $linea) use ($delimitador): bool {
            foreach (str_getcsv($linea, $delimitador) as $celda) {
                if (trim((string) $celda) !== '') {
                    return true;
                }
            }
            return false;
        };

        // El encabezado es la primera línea con contenido real.
        $indiceHeader = null;
        for ($i = 0; $i < count($lineas); $i++) {
            if ($tieneContenido($lineas[$i])) {
                $indiceHeader = $i;
                break;
            }
        }
        if ($indiceHeader === null) {
            return [];
        }

        $header = array_map(fn ($h) => $this->normalizarHeader($h), str_getcsv($lineas[$indiceHeader], $delimitador));

        $mapeoColumnas = [];
        foreach (self::COLUMNAS as $clave => $etiqueta) {
            $etiquetaNormalizada = $this->normalizarHeader($etiqueta);
            $posicion = array_search($etiquetaNormalizada, $header, true);
            $mapeoColumnas[$clave] = $posicion !== false ? $posicion : null;
        }

        $filas = [];
        for ($i = $indiceHeader + 1; $i < count($lineas); $i++) {
            if (!$tieneContenido($lineas[$i])) {
                continue; // saltar filas totalmente vacías o de solo delimitadores
            }
            $celdas = str_getcsv($lineas[$i], $delimitador);
            $fila = [];
            foreach ($mapeoColumnas as $clave => $posicion) {
                $fila[$clave] = $posicion !== null && isset($celdas[$posicion]) ? trim($celdas[$posicion]) : '';
            }
            $filas[] = $fila;
        }

        return $filas;
    }

    /**
     * Analiza una fila y devuelve la estructura con la acción a realizar.
     */
    private function analizarFila(array $fila, array $emailsVistos, int $entidadId, ?string $mesPagado = null): array
    {
        $mensajes = [];

        $nombre = trim((string) ($fila['nombre'] ?? ''));
        $apellido = trim((string) ($fila['apellido'] ?? ''));
        $mail = strtolower(trim((string) ($fila['mail'] ?? '')));
        $tk = strtoupper(trim((string) ($fila['tk'] ?? '')));
        $programa = trim((string) ($fila['programa'] ?? ''));
        ['online' => $online, 'suscripcion' => $suscripcion] = $this->parsearOnline($fila['online'] ?? '');
        $newsletter = $this->parsearSiNo($fila['newsletter'] ?? '');
        $telefono = trim((string) ($fila['telefonos'] ?? ''));
        $ciudad = trim((string) ($fila['ciudad'] ?? ''));

        // Email obligatorio y válido.
        if ($mail === '' || !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            return [
                'datos' => null,
                'accion' => 'error',
                'mensajes' => ['Email vacío o inválido'],
                'user_existe' => false,
                'membresia_id' => null,
                'programa_estudio_id' => null,
                'programa_a_distancia' => false,
                'password_propuesta' => null,
                'suscripcion' => false,
            ];
        }

        // Email duplicado dentro del mismo CSV.
        if (isset($emailsVistos[$mail])) {
            return [
                'datos' => null,
                'accion' => 'error',
                'mensajes' => ['Email duplicado dentro del CSV (ya procesado en una fila anterior)'],
                'user_existe' => false,
                'membresia_id' => null,
                'programa_estudio_id' => null,
                'programa_a_distancia' => false,
                'password_propuesta' => null,
                'suscripcion' => false,
            ];
        }

        $nombreCompleto = trim($nombre . ' ' . $apellido);
        if ($nombreCompleto === '') {
            $nombreCompleto = $mail;
            $mensajes[] = 'Nombre y apellido vacíos; se usó el email como nombre';
        }

        $userExistente = User::where('email', $mail)->first();

        // Lookup Membresia por abreviacion (TK), restringido a la Entidad seleccionada.
        $membresiaId = null;
        if ($tk !== '') {
            $membresia = Membresia::whereRaw('UPPER(abreviacion) = ?', [$tk])
                ->where('entidad_id', $entidadId)
                ->first();
            if ($membresia) {
                $membresiaId = $membresia->id;
            } else {
                $mensajes[] = "No hay Membresia con abreviación '{$tk}' para la Entidad seleccionada; el user se importa sin membresía";
            }
        }

        // Lookup ProgramaEstudio: primero por abreviacion (PG, PF, PFM...), fallback por nombre.
        // Si la abreviación viene con sufijo " Dis" (ej: "PF Dis"), se interpreta como "a distancia"
        // y se hace el lookup con la abreviación base (ej: "PF").
        $programaEstudioId = null;
        $programaADistancia = false;
        if ($programa !== '') {
            $programaNormalizado = trim($programa);
            // Detectar sufijo " Dis" (case-insensitive, con espacio previo).
            if (preg_match('/^(.*?)\s+Dis\s*$/i', $programaNormalizado, $matches)) {
                $programaADistancia = true;
                $programaNormalizado = trim($matches[1]);
            }

            $programaUpper = strtoupper($programaNormalizado);
            $pe = ProgramaEstudio::whereRaw('UPPER(TRIM(abreviacion)) = ?', [$programaUpper])->first();
            if (!$pe) {
                $pe = ProgramaEstudio::whereRaw('LOWER(TRIM(nombre)) = ?', [strtolower($programaNormalizado)])->first();
            }
            if ($pe) {
                $programaEstudioId = $pe->id;
            } else {
                $mensajes[] = "Programa de estudio '{$programa}' no encontrado (ni por abreviación ni por nombre); el user se importa sin programa";
                $programaADistancia = false;
            }
        }

        $passwordPropuesta = $this->generarPasswordPropuesta($apellido);

        // Si se marca como suscripción pero no hay membresía válida, advertimos.
        if ($suscripcion && !$membresiaId) {
            $mensajes[] = "Se indicó suscripción (asterisco en ONLINE) pero no hay membresía asociada; la suscripción se ignora";
        }

        // Para usuarios existentes: snapshot de la membresía ACTUAL y qué campos cambiarían,
        // para resaltarlos en el preview. La membresía solo se actualiza si hay TK válido
        // (membresia_id); si no, la membresía actual no se toca → no hay cambios.
        $actual = null;
        $cambios = null;
        if ($userExistente) {
            $perfil = $userExistente->membresiaUsuario; // hasOne (lazy load)
            $actualMembresiaId = $perfil?->membresia_id;
            $actualOnline = (bool) ($perfil?->membresia_online ?? false);
            $actualSuscripcion = (bool) ($perfil?->suscripcion ?? false);
            $actualAbrev = $actualMembresiaId
                ? optional(Membresia::find($actualMembresiaId))->abreviacion
                : null;
            $nuevaSuscripcion = $suscripcion && (bool) $membresiaId;

            $actual = [
                'tk' => $actualAbrev,
                'online' => $actualOnline,
                'suscripcion' => $actualSuscripcion,
            ];
            $cambios = [
                'membresia'   => $membresiaId ? ((int) $actualMembresiaId !== (int) $membresiaId) : false,
                'online'      => $membresiaId ? ($actualOnline !== $online) : false,
                'suscripcion' => $membresiaId ? ($actualSuscripcion !== $nuevaSuscripcion) : false,
            ];
        }

        // ¿Cambian los datos del usuario? (solo existentes; los nuevos siempre se crean).
        $cambiaUsuario = false;
        if ($userExistente) {
            $cambiaUsuario =
                ($userExistente->name !== $nombreCompleto)
                || ((string) ($userExistente->telefono ?? '') !== (string) ($telefono ?: ''))
                || ((string) ($userExistente->direccion ?? '') !== (string) ($ciudad ?: ''))
                || ((bool) $userExistente->msgxmail !== $newsletter)
                || ($programaEstudioId && (
                    (int) $userExistente->programa_estudio_id !== (int) $programaEstudioId
                    || (bool) $userExistente->programa_a_distancia !== $programaADistancia
                ));
        }

        // ¿Se escribe la membresía? Solo si hay TK válido y, para existentes, si algo cambia.
        $asignaraMembresia = (bool) $membresiaId && (
            !$userExistente
            || ($cambios['membresia'] ?? false)
            || ($cambios['online'] ?? false)
            || ($cambios['suscripcion'] ?? false)
        );

        // Existente sin ningún cambio (ni datos de usuario ni membresía): se omite la actualización.
        $sinCambios = (bool) $userExistente && !$cambiaUsuario && !$asignaraMembresia;

        // Pago del período elegido (columnas Imp./Día/Modo/Nota). Requiere membresía válida.
        $pago = $this->parsearPago($fila, $membresiaId, $mesPagado);
        if (($pago['sin_membresia'] ?? false)) {
            $mensajes[] = 'Se indicó un pago en el CSV pero el usuario no tiene una membresía (TK) válida para la entidad; el pago no se registra';
        }
        if (($pago['fuera_rango'] ?? false)) {
            $mensajes[] = "El importe del pago (\"{$pago['raw']['imp']}\") excede el máximo permitido; el pago no se registra. Revisá el valor en el CSV.";
        }

        return [
            'accion' => 'ok',
            'mensajes' => $mensajes,
            'pago' => $pago,
            'user_existe' => (bool) $userExistente,
            'membresia_id' => $membresiaId,
            'programa_estudio_id' => $programaEstudioId,
            'programa_a_distancia' => $programaADistancia,
            'password_propuesta' => $passwordPropuesta,
            'suscripcion' => $suscripcion && $membresiaId,
            'actual' => $actual,
            'cambios' => $cambios,
            'cambia_usuario' => $cambiaUsuario,
            'asignara_membresia' => $asignaraMembresia,
            'sin_cambios' => $sinCambios,
            'datos' => [
                'name' => $nombreCompleto,
                'mail' => $mail,
                'telefono' => $telefono ?: null,
                'ciudad' => $ciudad ?: null,
                'programa' => $programa,
                'tk' => $tk,
                'online' => $online,
                'newsletter' => $newsletter,
                'suscripcion' => $suscripcion && $membresiaId,
            ],
        ];
    }

    /**
     * Convierte SI/NO (con o sin asterisco) a boolean. Vacío = false.
     */
    private function parsearSiNo(?string $valor): bool
    {
        $v = strtoupper(trim((string) $valor));
        $v = rtrim($v, '*');
        return $v === 'SI' || $v === 'SÍ' || $v === 'YES' || $v === 'Y' || $v === '1';
    }

    /**
     * Parsea la columna ONLINE. El valor puede venir como "SI", "NO", "SI*" o "NO*".
     * El asterisco final indica que la membresía debe marcarse como "suscripción".
     * Devuelve ['online' => bool, 'suscripcion' => bool].
     */
    private function parsearOnline(?string $valor): array
    {
        $v = strtoupper(trim((string) $valor));
        $suscripcion = str_ends_with($v, '*');
        $v = rtrim($v, '*');
        $v = trim($v);
        $online = $v === 'SI' || $v === 'SÍ' || $v === 'YES' || $v === 'Y' || $v === '1';
        return ['online' => $online, 'suscripcion' => $suscripcion];
    }

    /**
     * Parsea las columnas de pago del mes en curso (Imp./Día/Modo/Nota) y arma el
     * payload para el estado de cuenta. Solo se registra si hay importe (monto o
     * "Sponsor") y una membresía válida. "--"/vacío => no se registra pago.
     */
    private function parsearPago(array $fila, ?int $membresiaId, ?string $mesPagado = null): array
    {
        $mesPagado ??= now()->format('Y-m');
        $raw = [
            'imp' => trim((string) ($fila['imp'] ?? '')),
            'dia' => trim((string) ($fila['dia'] ?? '')),
            'modo' => trim((string) ($fila['modo'] ?? '')),
            'nota' => trim((string) ($fila['nota'] ?? '')),
        ];

        $imp = $this->parsearImporte($raw['imp']);
        $esSponsor = $imp['tipo'] === 'sponsor';
        $hayPago = $imp['tipo'] === 'monto' || $esSponsor;

        if (!$hayPago) {
            return ['registrar' => false, 'sin_membresia' => false, 'raw' => $raw];
        }

        // Importe fuera del rango de la columna: no se registra el pago (se avisa en la fila).
        if ($imp['tipo'] === 'monto' && $imp['importe'] > self::IMPORTE_MAX) {
            return ['registrar' => false, 'sin_membresia' => false, 'fuera_rango' => true, 'raw' => $raw];
        }

        if (!$membresiaId) {
            return ['registrar' => false, 'sin_membresia' => true, 'raw' => $raw];
        }

        $modoInfo = $this->mapearModoPago($raw['modo']);
        $observaciones = $esSponsor
            ? ($raw['nota'] !== '' ? "Sponsor · {$raw['nota']}" : 'Sponsor')
            : $raw['nota'];

        return [
            'registrar' => true,
            'sin_membresia' => false,
            'es_sponsor' => $esSponsor,
            'mes_pagado' => $mesPagado,
            'importe' => $imp['importe'],
            'fecha_pago' => $this->parsearFechaPago($raw['dia'], $mesPagado),
            'modo' => $modoInfo['modo'],
            'info_pago' => $modoInfo['info_pago'],
            'observaciones' => $observaciones,
            'pagado' => true,
            'raw' => $raw,
        ];
    }

    /**
     * Interpreta el importe del CSV respetando el formato argentino ("." miles, "," decimal):
     * "$60.000" => 60000; "$60.000,50" => 60000.50; "$55,000" => 55000; "Sponsor" => tipo sponsor;
     * vacío o solo guiones ("--") => sin pago.
     */
    private function parsearImporte(string $valor): array
    {
        $v = trim($valor);
        if ($v === '' || preg_match('/^-+$/', $v)) {
            return ['tipo' => 'ninguno', 'importe' => 0.0];
        }
        if (Str::lower($v) === 'sponsor') {
            return ['tipo' => 'sponsor', 'importe' => 0.0];
        }

        // Dejamos solo dígitos y separadores; luego decidimos cuál es el decimal.
        $s = preg_replace('/[^\d.,]/', '', $v);
        if ($s === '' || preg_replace('/\D/', '', $s) === '') {
            return ['tipo' => 'ninguno', 'importe' => 0.0];
        }

        $tieneP = str_contains($s, '.');
        $tieneC = str_contains($s, ',');

        if ($tieneP && $tieneC) {
            // El separador que aparece más a la derecha es el decimal; el otro, de miles.
            if (strrpos($s, ',') > strrpos($s, '.')) {
                $s = str_replace('.', '', $s);      // "." = miles
                $s = str_replace(',', '.', $s);     // "," = decimal
            } else {
                $s = str_replace(',', '', $s);      // "," = miles, "." = decimal
            }
        } elseif ($tieneC) {
            $s = $this->normalizarSeparadorUnico($s, ',');
        } elseif ($tieneP) {
            $s = $this->normalizarSeparadorUnico($s, '.');
        }

        if (!is_numeric($s)) {
            return ['tipo' => 'ninguno', 'importe' => 0.0];
        }

        return ['tipo' => 'monto', 'importe' => (float) $s];
    }

    /**
     * Normaliza un número con un único tipo de separador ambiguo (solo "." o solo ","):
     * si hay un único separador seguido de 1-2 dígitos, es decimal; en cualquier otro caso
     * (grupos de 3, o varios separadores) es separador de miles y se descarta.
     */
    private function normalizarSeparadorUnico(string $s, string $sep): string
    {
        $partes = explode($sep, $s);
        if (count($partes) === 2 && strlen($partes[1]) >= 1 && strlen($partes[1]) <= 2) {
            return $partes[0] . '.' . $partes[1]; // decimal
        }

        return str_replace($sep, '', $s); // miles
    }

    /**
     * Parsea "dd/mm" (o "d/m", con o sin año) a fecha Y-m-d. Sin año, toma como
     * referencia el período elegido ($mesPagado, "YYYY-MM"; fallback al mes actual):
     * si el mes del pago resultara posterior al del período, usa el año anterior.
     */
    private function parsearFechaPago(string $valor, ?string $mesPagado = null): ?string
    {
        $v = trim($valor);
        if ($v === '' || !preg_match('#^(\d{1,2})/(\d{1,2})(?:/(\d{2,4}))?$#', $v, $m)) {
            return null;
        }

        $dia = (int) $m[1];
        $mes = (int) $m[2];
        if ($dia < 1 || $dia > 31 || $mes < 1 || $mes > 12) {
            return null;
        }

        if (isset($m[3]) && $m[3] !== '') {
            $anio = (int) $m[3];
            if ($anio < 100) {
                $anio += 2000;
            }
        } else {
            // Sin año en el CSV: elegimos el año que deja la fecha de pago más cerca del
            // período elegido (el pago suele caer en el mes del período o en uno adyacente).
            [$anioRef, $mesRef] = $this->partesMesPagado($mesPagado);
            $anio = $anioRef;
            $delta = $mes - $mesRef;
            if ($delta > 6) {
                $anio--; // ej. período enero + pago en diciembre => año anterior
            } elseif ($delta < -6) {
                $anio++; // ej. período diciembre + pago en enero => año siguiente
            }
        }

        try {
            return Carbon::create($anio, $mes, $dia)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Descompone un período "YYYY-MM" en [año, mes]. Si es inválido/null, usa el mes actual.
     */
    private function partesMesPagado(?string $mesPagado): array
    {
        if (is_string($mesPagado) && preg_match('/^(\d{4})-(\d{2})$/', $mesPagado, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }

        return [(int) now()->format('Y'), (int) now()->format('n')];
    }

    /**
     * Mapea el modo de pago abreviado del CSV al enum del modelo y conserva el texto
     * original (con su nº de referencia, ej. "MP6978") en info_pago.
     * Bco/B### => Transferencia, MPS => Suscripción, MP### => Otro (MercadoPago),
     * Efectivo => Efectivo, y cualquier otro => Otro.
     */
    private function mapearModoPago(string $valor): array
    {
        $v = trim($valor);
        if ($v === '') {
            return ['modo' => null, 'info_pago' => null];
        }

        $u = strtoupper(Str::ascii($v));

        if (str_starts_with($u, 'EFECTIVO')) {
            $modo = 'Efectivo';
        } elseif (str_starts_with($u, 'MPS')) {
            $modo = 'Suscripción';
        } elseif (str_starts_with($u, 'MP')) {
            $modo = 'Otro'; // MercadoPago (no está en el enum)
        } elseif (str_starts_with($u, 'B')) {
            $modo = 'Transferencia'; // Bco / Banco / B#### (referencia bancaria)
        } else {
            $modo = 'Otro'; // Anexo, USD, etc.
        }

        return ['modo' => $modo, 'info_pago' => $v];
    }

    /**
     * Genera password "ApellidoLimpio2026" sin tildes ni espacios.
     */
    private function generarPasswordPropuesta(string $apellido): string
    {
        $base = $apellido !== '' ? $apellido : 'usuario';
        $base = Str::ascii($base);
        $base = preg_replace('/[^A-Za-z0-9]/', '', $base);
        if ($base === '') {
            $base = 'usuario';
        }
        return $base . '2026';
    }

    /**
     * Normaliza header: trim, lowercase, sin tildes, sin espacios extra.
     */
    private function normalizarHeader(string $header): string
    {
        $h = trim($header);
        $h = Str::ascii($h);
        return strtolower($h);
    }
}

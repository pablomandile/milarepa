<?php

namespace App\Services;

use App\Models\Membresia;
use App\Models\ProgramaEstudio;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ImportarMembresiasService
{
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
    ];

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
    public function previsualizar(UploadedFile $archivo, int $entidadId): array
    {
        $filas = $this->parsearCsv($archivo);

        $emailsVistos = [];
        $resultado = [
            'total_filas' => count($filas),
            'filas_validas' => 0,
            'usuarios_nuevos' => 0,
            'usuarios_existentes' => 0,
            'membresias_a_asignar' => 0,
            'errores' => 0,
            'filas' => [],
        ];

        foreach ($filas as $index => $fila) {
            $linea = $index + 2; // +1 por el header, +1 por base 1
            $info = $this->analizarFila($fila, $emailsVistos, $entidadId);
            $info['linea'] = $linea;

            $resultado['filas'][] = $info;

            if ($info['accion'] === 'error') {
                $resultado['errores']++;
                continue;
            }

            $resultado['filas_validas']++;
            if ($info['user_existe']) {
                $resultado['usuarios_existentes']++;
            } else {
                $resultado['usuarios_nuevos']++;
            }
            if ($info['membresia_id']) {
                $resultado['membresias_a_asignar']++;
            }

            $emailsVistos[strtolower($info['datos']['mail'])] = true;
        }

        return $resultado;
    }

    /**
     * Ejecuta la importación. Devuelve los mismos contadores que previsualizar
     * pero con la operación efectiva ya aplicada.
     */
    public function importar(UploadedFile $archivo, int $entidadId): array
    {
        $filas = $this->parsearCsv($archivo);
        $emailsVistos = [];
        $resumen = [
            'total_filas' => count($filas),
            'creados' => 0,
            'actualizados' => 0,
            'membresias_asignadas' => 0,
            'errores' => 0,
            'detalle' => [],
        ];

        DB::transaction(function () use ($filas, $entidadId, &$emailsVistos, &$resumen) {
            foreach ($filas as $index => $fila) {
                $linea = $index + 2;
                $info = $this->analizarFila($fila, $emailsVistos, $entidadId);

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
                    $user->update($datosUser);
                    $resumen['actualizados']++;
                }

                if ($info['membresia_id']) {
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

                $resumen['detalle'][] = [
                    'linea' => $linea,
                    'resultado' => $esNuevo ? 'creado' : 'actualizado',
                    'email' => $info['datos']['mail'],
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
        $primeraLinea = $lineas[0];
        $delimitador = (substr_count($primeraLinea, ';') > substr_count($primeraLinea, ',')) ? ';' : ',';

        $header = str_getcsv($lineas[0], $delimitador);
        $header = array_map(fn ($h) => $this->normalizarHeader($h), $header);

        $mapeoColumnas = [];
        foreach (self::COLUMNAS as $clave => $etiqueta) {
            $etiquetaNormalizada = $this->normalizarHeader($etiqueta);
            $posicion = array_search($etiquetaNormalizada, $header, true);
            $mapeoColumnas[$clave] = $posicion !== false ? $posicion : null;
        }

        $filas = [];
        for ($i = 1; $i < count($lineas); $i++) {
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
    private function analizarFila(array $fila, array $emailsVistos, int $entidadId): array
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

        return [
            'accion' => 'ok',
            'mensajes' => $mensajes,
            'user_existe' => (bool) $userExistente,
            'membresia_id' => $membresiaId,
            'programa_estudio_id' => $programaEstudioId,
            'programa_a_distancia' => $programaADistancia,
            'password_propuesta' => $passwordPropuesta,
            'suscripcion' => $suscripcion && $membresiaId,
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

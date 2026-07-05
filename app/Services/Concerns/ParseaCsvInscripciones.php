<?php

namespace App\Services\Concerns;

use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Helpers compartidos por los importadores de inscripciones (legacy y multievento):
 * parseo de CSV desde un string, normalización de headers y parseo de montos/fechas/
 * membresías. Es la misma lógica del importador legacy, extraída para reutilizarla sin
 * cambiar su comportamiento.
 */
trait ParseaCsvInscripciones
{
    /**
     * Mapea el contenido de un CSV (string) a filas asociativas según $columnas
     * (clave interna => header esperado) e informa las columnas desconocidas (headers
     * que no están ni en $columnas ni en $columnasIgnoradas).
     *
     * @return array{0: array<int, array<string,string>>, 1: array<int, string>}
     *               [filas mapeadas, headers desconocidos (texto crudo)]
     */
    protected function mapearCsv(string $contenido, array $columnas, array $columnasIgnoradas = []): array
    {
        $contenido = $this->normalizarContenidoCsv($contenido);

        $filasCrudas = $this->leerFilasCsv($contenido);
        if (empty($filasCrudas)) {
            return [[], []];
        }

        $headerCrudo = $filasCrudas[0];
        $header = array_map(fn ($h) => $this->normalizarHeader($h), $headerCrudo);

        $mapeo = [];
        foreach ($columnas as $clave => $etiqueta) {
            $pos = array_search($this->normalizarHeader($etiqueta), $header, true);
            $mapeo[$clave] = $pos !== false ? $pos : null;
        }

        // Columnas desconocidas: headers no mapeados ni en la lista de ignorados conocidos.
        $conocidas = [];
        foreach (array_merge(array_values($columnas), $columnasIgnoradas) as $etiqueta) {
            $conocidas[$this->normalizarHeader($etiqueta)] = true;
        }
        $desconocidas = [];
        foreach ($headerCrudo as $h) {
            $norm = $this->normalizarHeader((string) $h);
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

    /** Detecta el encoding, convierte a UTF-8 y quita el BOM. */
    protected function normalizarContenidoCsv(string $contenido): string
    {
        $encoding = mb_detect_encoding($contenido, ['UTF-8', 'Windows-1252', 'ISO-8859-1'], true);
        if ($encoding && $encoding !== 'UTF-8') {
            $contenido = mb_convert_encoding($contenido, 'UTF-8', $encoding);
        }

        return preg_replace('/^\xEF\xBB\xBF/', '', $contenido);
    }

    /** Lee CSV con soporte de comillas y celdas multilínea (algunas columnas traen HTML con saltos). */
    protected function leerFilasCsv(string $contenido): array
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

    protected function normalizarHeader(string $h): string
    {
        $h = Str::ascii($h);
        $h = strtolower($h);
        $h = preg_replace('/[^a-z0-9]+/', ' ', $h);

        return trim($h);
    }

    protected function parsearMonto(?string $valor): ?float
    {
        $digitos = preg_replace('/[^0-9]/', '', (string) $valor);

        return $digitos === '' ? null : (float) $digitos;
    }

    protected function parsearFechaPago(?string $valor, int $anio): ?string
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

    protected function parsearMarcaTemporal(?string $valor): ?string
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

    protected function parsearSiNo(?string $valor): bool
    {
        $v = Str::upper(Str::ascii(trim((string) $valor)));

        return Str::startsWith($v, 'SI');
    }

    protected function generarPassword(string $apellido): string
    {
        $base = $apellido !== '' ? $apellido : 'asistente';
        $base = preg_replace('/[^A-Za-z0-9]/', '', Str::ascii($base));

        return ($base === '' ? 'asistente' : $base) . '2026';
    }

    /**
     * "TK CORAZÓN CMKA" / "SIN MEMBRESIA" => [membresia_id|null, nombre snapshot, mensaje|null].
     *
     * $ctx requiere: entidadActividad (int, entidad por defecto), entidades (Collection con
     * id/nombre/abreviacion/entidad_principal), membresias (Collection con id/nombre/entidad_id),
     * entidadPrincipal (int).
     */
    protected function resolverMembresia(string $raw, array $ctx): array
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
            $membresia = $this->buscarMembresia($ctx, (int) $entidadId, $tipo);

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
    protected function buscarMembresia(array $ctx, int $entidadId, string $tipo)
    {
        return $ctx['membresias']->first(function ($m) use ($entidadId, $tipo) {
            return (int) $m->entidad_id === $entidadId
                && Str::contains(Str::lower(Str::ascii($m->nombre)), $tipo);
        });
    }
}

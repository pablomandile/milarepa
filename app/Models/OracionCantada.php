<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OracionCantada extends Model
{
    use HasFactory;

    protected $table = 'oraciones_cantadas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'dia',
        'dias_semana',
        'hora',
        'horarios_por_dia',
        'periodicidad',
        'configuracion_por_mes',
        'excepciones_por_fecha',
        'modalidad_id',
        'stream_id',
        'imagen',
        'mostrar_en_calendario',
    ];

    protected $casts = [
        'dia' => 'integer',
        'dias_semana' => 'array',
        'horarios_por_dia' => 'array',
        'configuracion_por_mes' => 'array',
        'excepciones_por_fecha' => 'array',
        'mostrar_en_calendario' => 'boolean',
    ];

    public function configuracionParaMes(CarbonInterface $month): array
    {
        $base = [
            'periodicidad' => $this->periodicidad,
            'dia' => $this->dia,
            'dias_semana' => $this->dias_semana ?? [],
            'hora' => $this->hora,
            'horarios_por_dia' => $this->horarios_por_dia ?? [],
        ];

        $mes = (int) $month->format('n');
        $personalizada = collect($this->configuracion_por_mes ?? [])
            ->first(fn ($item) => (int) ($item['mes'] ?? 0) === $mes);

        if (!$personalizada) {
            return $base;
        }

        return [
            'periodicidad' => $personalizada['periodicidad'] ?? $base['periodicidad'],
            'dia' => $personalizada['dia'] ?? $base['dia'],
            'dias_semana' => $personalizada['dias_semana'] ?? $base['dias_semana'],
            'hora' => $personalizada['hora'] ?? $base['hora'],
            'horarios_por_dia' => $personalizada['horarios_por_dia'] ?? $base['horarios_por_dia'],
        ];
    }

    /**
     * Devuelve la hora de un día puntual (periodicidad Diaria) usando el horario
     * personalizado por día si existe, o la hora general como valor por defecto.
     */
    public function horaParaDia(array $configuracion, string $weekday): ?string
    {
        $horarios = $configuracion['horarios_por_dia'] ?? [];

        if (is_array($horarios) && !empty($horarios[$weekday])) {
            return $horarios[$weekday];
        }

        return $configuracion['hora'] ?? null;
    }

    /**
     * Busca una excepción puntual para una fecha concreta (formato Y-m-d). Las
     * excepciones se superponen a la configuración genérica: pueden cambiar la hora
     * de ese día o traer un mensaje (ej. "Centro cerrado").
     */
    public function excepcionParaFecha(string $fecha): ?array
    {
        return collect($this->excepciones_por_fecha ?? [])
            ->first(fn ($item) => is_array($item) && ($item['fecha'] ?? null) === $fecha);
    }

    /**
     * Genera las sesiones (fecha + hora + mensaje) de la oración dentro del mes,
     * respetando la configuración personalizada por mes y aplicando las excepciones
     * por fecha. Es la fuente única de verdad que consumen la grilla pública y el
     * calendario. Las excepciones solo decoran fechas que la config ya genera.
     */
    public function sesionesDelMes(CarbonInterface $monthStart, CarbonInterface $monthEnd): array
    {
        $weekdayMap = [
            1 => 'lunes', 2 => 'martes', 3 => 'miercoles', 4 => 'jueves',
            5 => 'viernes', 6 => 'sabado', 7 => 'domingo',
        ];

        $config = $this->configuracionParaMes($monthStart);
        $hora = $config['hora'] ? Carbon::parse($config['hora'])->format('H:i') : null;
        $sesiones = [];

        if ($config['periodicidad'] === 'Mensual') {
            $dia = (int) ($config['dia'] ?? 0);
            if ($dia === 29 && $monthStart->daysInMonth === 28) {
                $dia = 28;
            }

            if ($dia >= 1 && $dia <= $monthStart->daysInMonth) {
                $sesiones[] = $this->aplicarExcepcion($monthStart->copy()->day($dia)->toDateString(), $hora);
            }

            return $sesiones;
        }

        if ($config['periodicidad'] !== 'Diaria') {
            return $sesiones;
        }

        $diasSemana = collect($config['dias_semana'] ?? [])->map(fn ($d) => (string) $d);
        if ($diasSemana->isEmpty()) {
            return $sesiones;
        }

        for ($cursor = $monthStart->copy(); $cursor->lte($monthEnd); $cursor->addDay()) {
            $weekday = $weekdayMap[$cursor->dayOfWeekIso] ?? null;
            if (!$weekday || !$diasSemana->contains($weekday)) {
                continue;
            }

            $horaDia = $this->horaParaDia($config, $weekday);
            $sesiones[] = $this->aplicarExcepcion(
                $cursor->toDateString(),
                $horaDia ? Carbon::parse($horaDia)->format('H:i') : null
            );
        }

        return $sesiones;
    }

    /**
     * Devuelve el item de sesión {fecha, hora, mensaje} para una fecha, aplicando la
     * excepción puntual si existe: la hora de la excepción reemplaza a la de la config
     * y se adjunta el mensaje (o null si no hay excepción).
     */
    private function aplicarExcepcion(string $fecha, ?string $hora): array
    {
        $excepcion = $this->excepcionParaFecha($fecha);

        if ($excepcion && !empty($excepcion['hora'])) {
            $hora = Carbon::parse($excepcion['hora'])->format('H:i');
        }

        return [
            'fecha' => $fecha,
            'hora' => $hora,
            'mensaje' => $excepcion['mensaje'] ?? null,
        ];
    }

    public function stream()
    {
        return $this->belongsTo(Stream::class, 'stream_id');
    }

    public function modalidad()
    {
        return $this->belongsTo(Modalidad::class, 'modalidad_id');
    }
}

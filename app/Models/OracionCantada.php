<?php

namespace App\Models;

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

    public function stream()
    {
        return $this->belongsTo(Stream::class, 'stream_id');
    }

    public function modalidad()
    {
        return $this->belongsTo(Modalidad::class, 'modalidad_id');
    }
}

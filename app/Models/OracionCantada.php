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

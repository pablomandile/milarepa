<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class EmailEnvioConfiguracion extends Model
{
    public const DEFAULT_PLANTILLAS = [
        'inscripcion_registrada' => 'inscripcion_registrada.blade.php',
        'inscripcion_confirmada' => 'inscripcion_confirmada.blade.php',
        'envio_grabacion' => 'envio_grabacion.blade.php',
        'informacion_membresias' => 'informacion_membresias.blade.php',
        'inscripcion_tk_registrada' => 'inscripcion_tk_registrada.blade.php',
        'envio_actividades_online' => 'envio_Actividades_online.blade.php',
    ];

    protected $table = 'email_envio_configuraciones';

    protected $fillable = [
        'proceso_key',
        'proceso_nombre',
        'plantilla_archivo',
    ];

    public static function resolverPlantilla(string $procesoKey): array
    {
        $plantillaArchivo = static::query()
            ->where('proceso_key', $procesoKey)
            ->value('plantilla_archivo');

        if (empty($plantillaArchivo)) {
            $plantillaArchivo = self::DEFAULT_PLANTILLAS[$procesoKey] ?? '';
        }

        $nombrePlantilla = EmailPlantilla::query()
            ->where('plantilla_archivo', $plantillaArchivo)
            ->value('nombre');

        return [
            'archivo' => $plantillaArchivo,
            'view' => self::toViewName($plantillaArchivo),
            'nombre' => $nombrePlantilla ?: self::nombrePorDefecto($procesoKey),
        ];
    }

    private static function toViewName(string $plantillaArchivo): string
    {
        if (Str::startsWith($plantillaArchivo, 'emails.')) {
            return $plantillaArchivo;
        }

        $baseName = Str::endsWith($plantillaArchivo, '.blade.php')
            ? Str::beforeLast($plantillaArchivo, '.blade.php')
            : $plantillaArchivo;

        return 'emails.' . $baseName;
    }

    private static function nombrePorDefecto(string $procesoKey): string
    {
        return match ($procesoKey) {
            'inscripcion_registrada' => 'Inscripción Registrada',
            'inscripcion_confirmada' => 'Confirmación de Pago',
            'envio_grabacion' => 'Grabación Disponible',
            'informacion_membresias' => 'Información de Membresías',
            'inscripcion_tk_registrada' => 'Inscripción TK Registrada',
            'envio_actividades_online' => 'Actividades Online',
            default => 'Envío de Email',
        };
    }
}

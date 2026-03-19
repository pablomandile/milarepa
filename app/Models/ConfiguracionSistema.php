<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionSistema extends Model
{
    use HasFactory;

    protected $table = 'configuraciones_sistema';

    protected $fillable = [
        'clave',
        'valor',
        'valor_texto',
    ];

    protected $casts = [
        'valor' => 'boolean',
    ];

    public static function obtenerBoolean(string $clave, bool $default = false): bool
    {
        $registro = static::query()->where('clave', $clave)->first();

        if (!$registro) {
            return $default;
        }

        return (bool) $registro->valor;
    }

    public static function guardarBoolean(string $clave, bool $valor): void
    {
        static::query()->updateOrCreate(
            ['clave' => $clave],
            ['valor' => $valor]
        );
    }

    public static function obtenerTexto(string $clave, string $default = ''): string
    {
        $registro = static::query()->where('clave', $clave)->first();

        if (!$registro || is_null($registro->valor_texto)) {
            return $default;
        }

        return (string) $registro->valor_texto;
    }

    public static function guardarTexto(string $clave, ?string $valor): void
    {
        static::query()->updateOrCreate(
            ['clave' => $clave],
            [
                'valor' => false,
                'valor_texto' => $valor,
            ]
        );
    }
}

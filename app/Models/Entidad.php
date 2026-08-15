<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Entidad extends Model
{
    use HasFactory;
    
    protected $table = 'entidades';

    protected $fillable = [
        'nombre', 
        'descripcion', 
        'abreviacion',
        'direccion',
        'telefono',
        'whatsapp',
        'web_uri',
        'instagram_uri',
        'facebook_uri',
        'twitter_uri',
        'youtube_uri',
        'spotify_uri',
        'logo_uri',
        'email1',
        'email2',
        'entidad_principal'
    ];

    protected $appends = ['logo_url'];

    public function getLogoUrlAttribute()
    {
        if ($this->logo_uri) {
            // Si la URI ya es una URL completa (http/https), retornarla tal cual
            if (filter_var($this->logo_uri, FILTER_VALIDATE_URL)) {
                return $this->logo_uri;
            }
            // Si empieza con /storage/, retornarla tal cual
            if (str_starts_with($this->logo_uri, '/storage/')) {
                return $this->logo_uri;
            }
            // De lo contrario, agregar el prefijo /storage/
            return '/storage/' . ltrim($this->logo_uri, '/');
        }
        return null;
    }

    public function membresias()
    {
        return $this->hasMany(Membresia::class, 'entidad_id');
    }

    /**
     * Resuelve la sede que nombra una palabra suelta de la URL (?sede=rosario),
     * comparándola contra el nombre normalizado de cada entidad. Devuelve null
     * si no hay coincidencias o si hay más de una ("kadampa" está en varias):
     * las páginas públicas muestran todo en ese caso, nunca un error.
     */
    public static function resolverPorPalabra(?string $palabra): ?self
    {
        $clave = self::normalizarClave($palabra);
        if ($clave === '') {
            return null;
        }

        $coincidencias = self::all()->filter(
            fn (self $entidad) => str_contains(self::normalizarClave($entidad->nombre), $clave)
        );

        return $coincidencias->count() === 1 ? $coincidencias->first() : null;
    }

    /**
     * Minúsculas sin acentos ni separadores, para que "san-telmo", "San Telmo"
     * y "santelmo" comparen igual contra "Anexo San Telmo".
     */
    private static function normalizarClave(?string $texto): string
    {
        return preg_replace('/[^a-z0-9]/', '', Str::lower(Str::ascii((string) $texto)));
    }
}

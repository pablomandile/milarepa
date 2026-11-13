<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lugar extends Model
{
    use HasFactory;

    protected $table = 'lugares';

    protected $fillable = [
        'nombre',
        'descripcion',
        'direccion',
        'telefono',
        'whatsapp',
        'web_uri',
        'instagram_uri',
        'logo_uri',
        'email1',
        'email2',
    ];

    protected $appends = ['logo_url'];

    /**
     * Lugar para mostrar en una página pública: el lugar propio si existe (nombre
     * y dirección), y si no, la dirección de la entidad (sede). Null si no hay
     * ningún dato. Lo usan las páginas públicas de Clases y Oraciones cantadas.
     */
    public static function datosPublicos(?self $lugar, ?Entidad $entidad): ?array
    {
        if ($lugar) {
            $nombre = filled($lugar->nombre) ? trim($lugar->nombre) : null;
            $direccion = filled($lugar->direccion) ? trim($lugar->direccion) : null;
        } else {
            $nombre = null;
            $direccion = filled($entidad?->direccion) ? trim($entidad->direccion) : null;
        }

        if (!$nombre && !$direccion) {
            return null;
        }

        return ['nombre' => $nombre, 'direccion' => $direccion];
    }

    public function getLogoUrlAttribute()
    {
        if (!$this->logo_uri) {
            return null;
        }

        if (filter_var($this->logo_uri, FILTER_VALIDATE_URL)) {
            return $this->logo_uri;
        }

        if (str_starts_with($this->logo_uri, '/storage/')) {
            return $this->logo_uri;
        }

        return '/storage/' . ltrim($this->logo_uri, '/');
    }
}

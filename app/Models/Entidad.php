<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}

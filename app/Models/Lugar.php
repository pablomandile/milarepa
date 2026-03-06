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

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
        'direccion',
        'telefono',
        'whatsapp',
        'web_uri',
        'instagram_uri',
        'facebook_uri',
        'twitter_uri',
        'youtube_uri',
        'logo_uri',
        'email1',
        'email2',
        'entidad_principal'
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestUser extends Model
{
    use HasFactory;

    protected $table = 'guest_users';

    protected $fillable = [
        'name',
        'email',
        'telefono',
        'whatsapp',
        'pais_id',
        'provincia_id',
        'municipio_id',
        'barrio_id',
        'direccion',
        'msgxmail',
        'msgxwapp',
        'accesibilidad',
        'accesibilidad_desc',
    ];

    public function inscripciones()
    {
        return $this->hasMany(Inscripcion::class, 'guest_user_id');
    }

    public function pais()
    {
        return $this->belongsTo(Pais::class, 'pais_id');
    }

    public function provincia()
    {
        return $this->belongsTo(Provincia::class, 'provincia_id');
    }

    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function barrio()
    {
        return $this->belongsTo(Barrio::class, 'barrio_id');
    }
}

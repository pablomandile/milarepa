<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InscripcionComprobante extends Model
{
    use HasFactory;

    protected $table = 'inscripcion_comprobantes';

    protected $fillable = [
        'inscripcion_id',
        'ruta',
        'descripcion',
    ];

    public function inscripcion()
    {
        return $this->belongsTo(Inscripcion::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clase extends Model
{
    use HasFactory;

    protected $table = 'clases';

    protected $fillable = [
        'nombre',
        'ciclo_id',
        'entidad_id',
        'mes_referencia',
        'descripcion',
        'imagen_id',
        'dias_semana',
        'titulos_por_fecha',
        'horario_desde',
        'horario_hasta',
        'coordinador_id',
        'esquema_precio_id',
        'modalidad_id',
        'stream_id',
        'mostrar_en_calendario',
        'activa',
    ];

    protected $casts = [
        'dias_semana' => 'array',
        'titulos_por_fecha' => 'array',
        'mostrar_en_calendario' => 'boolean',
        'activa' => 'boolean',
    ];

    public function ciclo()
    {
        return $this->belongsTo(Ciclo::class, 'ciclo_id');
    }

    public function entidad()
    {
        return $this->belongsTo(Entidad::class, 'entidad_id');
    }

    public function maestros()
    {
        return $this->belongsToMany(Maestro::class, 'clase_maestro')->withTimestamps();
    }

    public function coordinador()
    {
        return $this->belongsTo(Coordinador::class, 'coordinador_id');
    }

    public function esquemaPrecio()
    {
        return $this->belongsTo(EsquemaPrecio::class, 'esquema_precio_id');
    }

    public function modalidad()
    {
        return $this->belongsTo(Modalidad::class, 'modalidad_id');
    }

    public function stream()
    {
        return $this->belongsTo(Stream::class, 'stream_id');
    }

    public function imagen()
    {
        return $this->belongsTo(Imagen::class, 'imagen_id');
    }

    public function inscripcionesClases()
    {
        return $this->hasMany(InscripcionClase::class, 'clase_id');
    }
}

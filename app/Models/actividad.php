<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Actividad extends Model
{
    use HasFactory;
    
    protected $table = 'actividades';

    protected $fillable = [
        'tipo_actividad_id', 
        'nombre',
        'descripcion_id',
        'observaciones',
        'imagen_id', 
        'fecha_inicio', 
        'fecha_fin', 
        'pagoAmticipado', 
        'entidad_id', 
        'disponibilidad_id', 
        'modalidad_id', 
        'esquema_precio_id', 
        'esquema_descuento_id', 
        'link_web', 
        'grabacion_id',
        'grabacion_importe',
        'stream_id', 
        'programa_id',
        'estado'
    ];

    protected $casts = [
        'estado' => 'boolean',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'grabacion_importe' => 'decimal:2',
    ];

    public function metodosPago() 
    { 
        return $this->belongsToMany(MetodoPago::class); 
    }

    public function hospedajes() 
    { 
        return $this->belongsToMany(Hospedaje::class); 
    }

    public function comidas() 
    { 
        return $this->belongsToMany(Comida::class); 
    }

    public function transportes() 
    { 
        return $this->belongsToMany(Transporte::class); 
    }

    public function coordinadores() 
    { 
        return $this->belongsToMany(Coordinador::class); 
    }

    public function maestros() 
    { 
        return $this->belongsToMany(Maestro::class); 
    }

    public function imagen()
    {
        return $this->belongsTo(Imagen::class, 'imagen_id');
    }

    public function tipoActividad()
    {
        return $this->belongsTo(TipoActividad::class, 'tipo_actividad_id');
    }

    public function descripcion()
    {
        return $this->belongsTo(Descripcion::class, 'descripcion_id');
    }
    
    public function entidad()
    {
        return $this->belongsTo(Entidad::class, 'entidad_id');
    }
    
    public function disponibilidad()
    {
        return $this->belongsTo(Disponibilidad::class, 'disponibilidad_id');
    }
    
    public function modalidad()
    {
        return $this->belongsTo(Modalidad::class, 'modalidad_id');
    }
    
    public function esquemaPrecio()
    {
        return $this->belongsTo(EsquemaPrecio::class, 'esquema_precio_id');
    }
    
    public function esquemaDescuento()
    {
        return $this->belongsTo(EsquemaDescuento::class, 'esquema_descuento_id');
    }
    
    public function stream()
    {
        return $this->belongsTo(Stream::class, 'stream_id');
    }

    public function grabacion()
    {
        return $this->belongsTo(Grabacion::class, 'grabacion_id');
    }
    
    public function programa()
    {
        return $this->belongsTo(Programa::class, 'programa_id');
    }

}

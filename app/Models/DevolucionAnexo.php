<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DevolucionAnexo extends Model
{
    use HasFactory;

    protected $table = 'devoluciones_anexos';

    protected $fillable = [
        'fecha',
        'devolvedor_id',
        'prestador_id',
        'libro_id',
        'cantidad',
        'user_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'cantidad' => 'integer',
    ];

    public function devolvedor()
    {
        return $this->belongsTo(Entidad::class, 'devolvedor_id');
    }

    public function prestador()
    {
        return $this->belongsTo(Entidad::class, 'prestador_id');
    }

    public function libro()
    {
        return $this->belongsTo(Libro::class, 'libro_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrecioGrupo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'precio_grupos';

    protected $fillable = [
        'nombre',
        'fecha_desde',
    ];

    protected $casts = [
        'fecha_desde' => 'date',
    ];

    public function lineas()
    {
        return $this->hasMany(PrecioGrupoMembresia::class, 'precio_grupo_id');
    }
}

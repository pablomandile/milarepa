<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrecioGrupoMembresia extends Model
{
    use HasFactory;

    protected $table = 'precio_grupo_membresias';

    protected $fillable = [
        'precio_grupo_id',
        'membresia_id',
        'valor',
    ];

    protected $casts = [
        'valor' => 'decimal:2',
    ];

    public function precioGrupo()
    {
        return $this->belongsTo(PrecioGrupo::class, 'precio_grupo_id');
    }

    public function membresia()
    {
        return $this->belongsTo(Membresia::class, 'membresia_id');
    }
}

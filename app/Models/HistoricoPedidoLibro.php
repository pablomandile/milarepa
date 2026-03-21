<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoricoPedidoLibro extends Model
{
    use HasFactory;

    protected $table = 'historico_pedidos_libros';

    protected $fillable = [
        'fecha',
        'libro_id',
        'entidad_id',
        'cantidad_total',
        'cantidad_inicial',
        'cantidad_vendida',
        'cantidad_final',
        'importe',
        'vendedor_id',
        'email_comprador',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'cantidad_total' => 'integer',
        'cantidad_inicial' => 'integer',
        'cantidad_vendida' => 'integer',
        'cantidad_final' => 'integer',
        'importe' => 'decimal:2',
    ];

    public function libro()
    {
        return $this->belongsTo(Libro::class, 'libro_id');
    }

    public function vendedor()
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    public function entidad()
    {
        return $this->belongsTo(Entidad::class, 'entidad_id');
    }
}

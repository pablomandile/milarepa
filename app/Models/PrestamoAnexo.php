<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrestamoAnexo extends Model
{
    use HasFactory;

    protected $table = 'prestamos_anexos';

    protected $fillable = [
        'fecha',
        'prestadora_id',
        'receptora_id',
        'libro_id',
        'cantidad',
        'user_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'cantidad' => 'integer',
    ];

    public function prestadora()
    {
        return $this->belongsTo(Entidad::class, 'prestadora_id');
    }

    public function receptora()
    {
        return $this->belongsTo(Entidad::class, 'receptora_id');
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

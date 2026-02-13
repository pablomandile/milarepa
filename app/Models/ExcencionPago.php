<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExcencionPago extends Model
{
    use HasFactory;

    protected $table = 'excencion_pago';

    protected $fillable = [
        'user_id',
        'actividad_id',
        'importe',
    ];

    protected $casts = [
        'importe' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function actividad()
    {
        return $this->belongsTo(Actividad::class, 'actividad_id');
    }
}

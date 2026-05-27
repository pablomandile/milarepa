<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramaGrabacion extends Model
{
    use HasFactory;

    protected $table = 'programa_grabacion';

    protected $fillable = [
        'programa_estudio_id',
        'nombre',
        'descripcion',
        'archivo',
        'size_bytes',
        'mime_type',
    ];

    public function programaEstudio(): BelongsTo
    {
        return $this->belongsTo(ProgramaEstudio::class, 'programa_estudio_id');
    }
}

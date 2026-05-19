<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FraseDeDharma extends Model
{
    use HasFactory;

    protected $table = 'frases_de_dharma';

    protected $fillable = [
        'numero',
        'cita_textual',
        'libro',
    ];

    protected $casts = [
        'numero' => 'integer',
    ];
}

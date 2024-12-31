<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;
    
    protected $table = 'tickets';

    protected $fillable = [
        'asunto',
        'descripcion',
        'fecha_apertura',
        'user_id',
        'estadoticket_id',
        'responsable_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function estadoTicket()
    {
        return $this->belongsTo(EstadoTicket::class, 'estadoticket_id');
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }
}

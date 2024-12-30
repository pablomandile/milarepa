<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoTicket extends Model
{
    use HasFactory;
    
    protected $table = 'estados_ticket';

    public function tickets()
{
    return $this->hasMany(Ticket::class, 'estadoticket_id');
}

}

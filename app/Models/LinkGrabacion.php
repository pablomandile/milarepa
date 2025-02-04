<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LinkGrabacion extends Model
{
    use HasFactory;
    
    protected $table = 'links_grabacion';

    protected $fillable = [
        'nombre',
        'link',
        'grabacion_id'
    ];
    
    public function grabacion()
    {
        return $this->belongsTo(Grabacion::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grabacion extends Model
{
    use HasFactory;
    
    protected $table = 'grabaciones';

    protected $fillable = [
        'nombre'
    ];
    
    public function linksgrabacion()
    {
        return $this->hasMany(LinkGrabacion::class);
    }
}

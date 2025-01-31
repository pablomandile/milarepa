<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stream extends Model
{
    use HasFactory;
    
    protected $table = 'streams';

    protected $fillable = [
        'nombre'
    ];
    
    public function links()
    {
        return $this->hasMany(Link::class);
    }
}

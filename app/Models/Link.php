<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
    use HasFactory;
    
    protected $table = 'links';

    protected $fillable = [
        'nombre',
        'link',
        'stream_id'
    ];
    
    public function stream()
    {
        return $this->belongsTo(Stream::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Moneda extends Model
{
    use HasFactory;

    protected $table = 'monedas';

    protected $fillable = [
        'nombre',
        'simbolo',
        'es_principal',
    ];

    protected $casts = [
        'es_principal' => 'boolean',
    ];

    /**
     * Moneda principal del sistema (la de los precios planos y de todo lo
     * legacy sin moneda_id). Query directa, sin cache estático: se envenenaría
     * entre tests con DatabaseTransactions.
     */
    public static function principal(): ?self
    {
        return static::where('es_principal', true)->first();
    }

    public static function principalId(): ?int
    {
        return static::principal()?->id;
    }
}

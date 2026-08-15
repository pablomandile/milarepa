<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `comidas.precio`, `hospedajes.precio` y `transportes.precio` eran double(8,2):
 * coma flotante para guardar plata, que acumula errores de centavo al sumarse
 * (y estos precios se suman entre sí y con los de servicio_precios en cada
 * inscripción). Pasan a decimal(10,2), que es lo que ya usan
 * `grabaciones.valor` y `servicio_precios.precio`.
 *
 * Ampliar de (8,2) a (10,2) no puede truncar nada: sólo agrega capacidad.
 */
return new class extends Migration
{
    private array $tablas = ['comidas', 'hospedajes', 'transportes'];

    public function up(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                // change() re-declara la columna entera: hay que repetir el NOT NULL.
                $table->decimal('precio', 10, 2)->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->double('precio', 8, 2)->nullable(false)->change();
            });
        }
    }
};

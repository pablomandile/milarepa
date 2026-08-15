<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Precios en monedas NO principales para los servicios de actividades
     * (grabación, hospedaje, comida, transporte). El precio en la moneda
     * principal sigue viviendo en la columna plana de cada servicio
     * (`valor`/`precio`). Espejo del criterio de esquema_precio_membresias:
     * botonpago_id nullable sin FK.
     */
    public function up(): void
    {
        Schema::create('servicio_precios', function (Blueprint $table) {
            $table->id();
            $table->morphs('servicioable');
            $table->foreignId('moneda_id')->constrained('monedas')->onDelete('restrict');
            $table->decimal('precio', 10, 2);
            $table->foreignId('botonpago_id')->nullable();
            $table->timestamps();
            $table->unique(['servicioable_type', 'servicioable_id', 'moneda_id'], 'servicio_precios_unico');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicio_precios');
    }
};

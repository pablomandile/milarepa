<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('actividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tipo_actividad_id')->constrained('tipos_actividad');
            $table->string('nombre', 30);
            $table->string('descripcion', 50);
            $table->string('imageUri', 255);
            $table->date('fechaInicio');
            $table->date('fechaFin');
            $table->time('horaInicio');
            $table->foreignId('entidad_id')->constrained('entidades');
            $table->foreignId('disponibilidad_id')->constrained('disponibilidades');
            $table->foreignId('maestro_id')->constrained('maestros');
            $table->foreignId('coordinador_id')->constrained('coordinadores');
            $table->foreignId('moneda_id')->constrained('monedas');
            $table->foreignId('esquemaPrecios_id')->constrained('esquema_precios');
            $table->foreignId('aplicaDescuentoLugares_id')->constrained('aplica_descuento_lugares');
            $table->foreignId('esquemaDescuentos_id')->constrained('esquema_descuentos');
            $table->date('pagoAmticipado');
            $table->string('linkGrabacion', 255);
            $table->string('linkWeb', 255);
            $table->string('linkStreaming', 255);
            $table->text('programa');
            $table->text('infoExtra');
            $table->boolean('online');
            $table->foreignId('metodosPago_id')->constrained('metodos_pago');
            $table->foreignId('comida_id')->constrained('comidas');
            $table->foreignId('hospedaje_id')->constrained('hospedajes');
            $table->foreignId('transporte_id')->constrained('transportes');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actividades');
    }
};

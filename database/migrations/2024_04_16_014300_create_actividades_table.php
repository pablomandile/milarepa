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
            $table->unsignedInteger('tipo_actividad_id');
            $table->string('nombre', 30);
            $table->string('descripcion', 50);
            $table->string('imageUri', 255);
            $table->date('fechaInicio');
            $table->date('fechaFin');
            $table->time('horaInicio');
            $table->unsignedInteger('lugar_id');
            $table->unsignedInteger('disponibilidad_id');
            $table->unsignedInteger('maestro_id');
            $table->unsignedInteger('coordinador_id');
            $table->unsignedInteger('moneda_id');
            $table->unsignedInteger('esquemaPrecios_id');
            $table->unsignedInteger('aplicaDescuento_id');
            $table->unsignedInteger('esquemaDescuentos_id');
            $table->date('pagoAmticipado');
            $table->string('linkGrabacion', 255);
            $table->string('linkWeb', 255);
            $table->string('linkStreaming', 255);
            $table->text('programa');
            $table->text('infoExtra');
            $table->boolean('online');
            $table->unsignedInteger('metodosPago_id');
            $table->unsignedInteger('comidas_id');
            $table->unsignedInteger('hospedaje_id');
            $table->unsignedInteger('transporte_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actividads');
    }
};

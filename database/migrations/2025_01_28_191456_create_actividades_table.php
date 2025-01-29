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
            $table->unsignedBigInteger('tipo_actividad_id');
            $table->foreign('tipo_actividad_id')->references('id')->on('tipos_actividad')
            ->onDelete('cascade');
            $table->string('nombre', 80);
            $table->unsignedBigInteger('descripcion_id');
            $table->foreign('descripcion_id')->references('id')->on('descripciones')
            ->onDelete('cascade');
            $table->text('observaciones')->nullable();
            $table->unsignedBigInteger('imagen_id')->nullable();
            $table->foreign('imagen_id')->references('id')->on('imagenes')
            ->onDelete('cascade');
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');
            $table->dateTime('pagoAmticipado')->nullable();
            $table->unsignedBigInteger('entidad_id');
            $table->foreign('entidad_id')->references('id')->on('entidades')
            ->onDelete('cascade');
            $table->unsignedBigInteger('disponibilidad_id')->nullable();
            $table->foreign('disponibilidad_id')->references('id')->on('disponibilidades')
            ->onDelete('cascade');
            $table->unsignedBigInteger('modalidad_id');
            $table->foreign('modalidad_id')->references('id')->on('modalidades')
            ->onDelete('cascade');
            $table->unsignedBigInteger('esquema_precio_id');
            $table->foreign('esquema_precio_id')->references('id')->on('esquema_precios')
            ->onDelete('cascade');
            $table->unsignedBigInteger('esquema_descuento_id')->nullable();
            $table->foreign('esquema_descuento_id')->references('id')->on('esquema_descuentos')
            ->onDelete('cascade');
            $table->string('link_grabacion')->nullable();
            $table->string('link_web')->nullable();
            $table->unsignedBigInteger('stream_id')->nullable();
            $table->foreign('stream_id')->references('id')->on('streams')
            ->onDelete('cascade');
            $table->unsignedBigInteger('programa_id')->nullable();
            $table->foreign('programa_id')->references('id')->on('programas')
            ->onDelete('cascade');
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

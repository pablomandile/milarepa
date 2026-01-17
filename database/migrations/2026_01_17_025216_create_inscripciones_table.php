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
        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actividad_id');
            $table->unsignedBigInteger('user_id');
            $table->string('membresia');
            $table->decimal('precioGeneral', 10, 2);
            $table->decimal('montoapagar', 10, 2);
            $table->enum('pago', ['total', 'parcial', 'impago']);
            $table->unsignedBigInteger('estado_id');
            $table->enum('envioLinkStream', ['enviado', 'pendiente']);
            $table->enum('envioGrabación', ['enviada', 'pendiente']);
            $table->string('comprobante')->nullable();
            $table->enum('asistencia', ['presente', 'ausente']);
            $table->boolean('online');
            $table->unsignedBigInteger('hospedaje_id')->nullable();
            $table->unsignedBigInteger('comida_id')->nullable();
            $table->unsignedBigInteger('transporte_id')->nullable();
            $table->timestamps();

            $table->foreign('actividad_id')->references('id')->on('actividades');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('estado_id')->references('id')->on('estados_ticket'); // Corregido
            $table->foreign('hospedaje_id')->references('id')->on('hospedajes')->onDelete('set null');
            $table->foreign('comida_id')->references('id')->on('comidas')->onDelete('set null');
            $table->foreign('transporte_id')->references('id')->on('transportes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscripciones');
    }
};

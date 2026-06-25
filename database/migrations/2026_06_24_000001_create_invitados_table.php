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
        Schema::create('invitados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscripcion_id')->constrained('inscripciones')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('apellido');
            $table->string('telefono')->nullable();
            $table->boolean('online')->default(false);
            $table->enum('asistencia', ['Presente', 'Ausente', 'Pendiente'])->default('Pendiente');
            $table->boolean('incluye_grabacion')->default(false);
            $table->decimal('montoActividad', 10, 2)->default(0);
            $table->decimal('montoGrabacion', 10, 2)->nullable();
            $table->decimal('montoComidas', 10, 2)->nullable();
            $table->decimal('montoTransporte', 10, 2)->nullable();
            $table->decimal('montoHospedaje', 10, 2)->nullable();
            $table->decimal('montoapagar', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitados');
    }
};

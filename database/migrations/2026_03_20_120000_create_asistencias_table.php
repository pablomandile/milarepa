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
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscripcion_id')->nullable()->constrained('inscripciones')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('inscripcion_clase_id')->nullable()->constrained('clases')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->enum('asistencia', ['Presente', 'Ausente', 'Pendiente'])->default('Pendiente');
            $table->timestamps();

            $table->index(['inscripcion_id', 'usuario_id']);
            $table->index('asistencia');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};

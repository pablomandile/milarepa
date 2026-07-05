<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla (temporal, del paralelo de sistemas) que recuerda el match evento→actividad
 * confirmado en cada importación multievento, para prellenarlo en las siguientes.
 * Es descartable: se puede hacer `drop` cuando termine la migración de inscripciones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('multievento_mapeos', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique(); // NombreEvento||YYYY-MM-DD
            $table->string('nombre_evento');
            $table->date('fecha_evento')->nullable();
            $table->foreignId('actividad_id')->constrained('actividades')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('multievento_mapeos');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Líneas de producto (4 categorías Tharpa) de una inscripción a clase.
     * Reemplaza el texto concatenado `articulos_tharpa` como fuente de la selección:
     * permite reconstruir por ID (no por título) y descontar/devolver stock por categoría.
     */
    public function up(): void
    {
        Schema::create('inscripcion_clase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscripcion_clase_id')->constrained('inscripciones_clases')->cascadeOnDelete();
            $table->string('categoria', 20); // libro | oracion | arte | otro
            $table->morphs('producto');      // producto_type (alias morphMap) + producto_id
            $table->unsignedInteger('cantidad')->default(1);
            $table->decimal('precio_unitario', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripcion_clase_items');
    }
};

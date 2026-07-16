<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Líneas del ticket POS. `vendible` apunta al registro creado por la línea:
     * Venta (libro), Inscripcion, InscripcionClase, o el propio item (oración/arte/otro).
     * `cobro_id` enlaza el cobro del ledger correspondiente a la línea.
     */
    public function up(): void
    {
        Schema::create('venta_pos_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_pos_id')->constrained('venta_pos')->cascadeOnDelete();
            $table->enum('tipo', ['libro', 'oracion', 'arte', 'otro', 'inscripcion_actividad', 'inscripcion_clase']);
            $table->nullableMorphs('vendible');
            $table->foreignId('cobro_id')->nullable()->constrained('cobros')->nullOnDelete();
            $table->unsignedInteger('cantidad')->default(1);
            $table->decimal('precio_unitario', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_pos_items');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stock de cada ítem de arte por entidad (espejo de inventario_entidad_libro).
     */
    public function up(): void
    {
        Schema::create('inventario_entidad_arte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entidad_id')->constrained('entidades')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('arte_id')->constrained('arte')->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedInteger('cantidad')->default(0);
            $table->timestamps();

            $table->unique(['entidad_id', 'arte_id'], 'inventario_entidad_arte_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_entidad_arte');
    }
};

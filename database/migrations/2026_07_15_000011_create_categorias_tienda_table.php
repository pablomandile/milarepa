<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Categorías administrables de la Tienda general (sahumerios, zafus, adornos, etc.).
     * A diferencia de Tharpa (categorías fijas por tabla), acá son datos editables.
     */
    public function up(): void
    {
        Schema::create('categorias_tienda', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120)->unique();
            $table->unsignedInteger('orden')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_tienda');
    }
};

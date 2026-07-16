<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo de "Otros" de la tienda Tharpa (cajón de sastre: artículos que no
     * son libro, oración ni arte). El `tipo` es una etiqueta de texto libre.
     */
    public function up(): void
    {
        Schema::create('otros', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 255);
            $table->text('descripcion')->nullable();
            $table->string('tipo', 60)->nullable();
            $table->foreignId('imagen_id')->nullable()->constrained('imagenes')->cascadeOnUpdate()->nullOnDelete();
            $table->decimal('precio', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otros');
    }
};

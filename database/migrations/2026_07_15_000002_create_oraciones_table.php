<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo de "Oraciones" de la tienda Tharpa (librillos y audio).
     */
    public function up(): void
    {
        Schema::create('oraciones', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 255);
            $table->text('descripcion')->nullable();
            $table->string('tipo', 20);
            $table->foreignId('imagen_id')->nullable()->constrained('imagenes')->cascadeOnUpdate()->nullOnDelete();
            $table->decimal('precio', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oraciones');
    }
};

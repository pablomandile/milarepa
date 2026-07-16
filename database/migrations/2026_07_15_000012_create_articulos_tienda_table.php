<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo de artículos de la Tienda general. Cada artículo pertenece a una
     * categoría (FK, dinámica) y tiene precio + imagen opcional (molde: oraciones).
     */
    public function up(): void
    {
        Schema::create('articulos_tienda', function (Blueprint $table) {
            $table->id();
            $table->foreignId('categoria_tienda_id')->constrained('categorias_tienda')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('titulo', 255);
            $table->text('descripcion')->nullable();
            $table->foreignId('imagen_id')->nullable()->constrained('imagenes')->cascadeOnUpdate()->nullOnDelete();
            $table->decimal('precio', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articulos_tienda');
    }
};

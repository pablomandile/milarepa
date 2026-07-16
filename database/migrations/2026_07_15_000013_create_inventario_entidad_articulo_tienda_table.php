<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stock de cada artículo de tienda por entidad (espejo de inventario_entidad_oracion).
     */
    public function up(): void
    {
        Schema::create('inventario_entidad_articulo_tienda', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entidad_id')->constrained('entidades')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('articulo_tienda_id')->constrained('articulos_tienda')->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedInteger('cantidad')->default(0);
            $table->timestamps();

            $table->unique(['entidad_id', 'articulo_tienda_id'], 'inventario_entidad_articulo_tienda_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_entidad_articulo_tienda');
    }
};

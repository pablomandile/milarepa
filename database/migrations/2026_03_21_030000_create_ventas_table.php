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
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->foreignId('entidad_id')->constrained('entidades')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('libro_id')->constrained('libros')->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario', 10, 2)->default(0);
            $table->decimal('montoTotal', 10, 2)->default(0);
            $table->string('modo', 80);
            $table->foreignId('comprobante_id')->nullable()->constrained('imagenes')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('vendedor_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();

            $table->index('fecha');
            $table->index('entidad_id');
            $table->index('libro_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};

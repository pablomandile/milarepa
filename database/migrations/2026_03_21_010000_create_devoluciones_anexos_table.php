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
        Schema::create('devoluciones_anexos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->foreignId('devolvedor_id')->constrained('entidades')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('prestador_id')->constrained('entidades')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('libro_id')->constrained('libros')->cascadeOnUpdate()->restrictOnDelete();
            $table->unsignedInteger('cantidad');
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->timestamps();

            $table->index('fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('devoluciones_anexos');
    }
};

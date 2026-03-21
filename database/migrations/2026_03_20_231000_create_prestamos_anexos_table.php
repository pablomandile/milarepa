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
        Schema::create('prestamos_anexos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->foreignId('prestadora_id')->constrained('entidades')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('receptora_id')->constrained('entidades')->cascadeOnUpdate()->restrictOnDelete();
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
        Schema::dropIfExists('prestamos_anexos');
    }
};

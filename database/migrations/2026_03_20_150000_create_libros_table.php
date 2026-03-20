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
        Schema::create('libros', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 255);
            $table->text('descripcion')->nullable();
            $table->string('isbn', 100)->nullable();
            $table->string('autor', 255)->nullable();
            $table->string('editorial', 255)->nullable();
            $table->foreignId('imagen_id')->nullable()->constrained('imagenes')->cascadeOnUpdate()->nullOnDelete();
            $table->decimal('precio', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('libros');
    }
};

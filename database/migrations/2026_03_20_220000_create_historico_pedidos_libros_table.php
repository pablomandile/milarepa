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
        Schema::create('historico_pedidos_libros', function (Blueprint $table) {
            $table->id();
            $table->dateTime('fecha');
            $table->foreignId('libro_id')->constrained('libros')->cascadeOnUpdate()->cascadeOnDelete();
            $table->unsignedInteger('cantidad_inicial');
            $table->unsignedInteger('cantidad_vendida');
            $table->unsignedInteger('cantidad_final');
            $table->decimal('importe', 10, 2)->default(0);
            $table->foreignId('vendedor_id')->nullable()->constrained('users')->cascadeOnUpdate()->nullOnDelete();
            $table->string('email_comprador', 255)->nullable();
            $table->timestamps();

            $table->index('fecha');
            $table->index('vendedor_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historico_pedidos_libros');
    }
};

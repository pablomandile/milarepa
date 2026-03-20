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
        Schema::create('inscripciones_clases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clase_id')->constrained('clases')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('membresia');
            $table->decimal('precioGeneral', 10, 2);
            $table->decimal('montoActividad', 10, 2)->default(0.00);
            $table->decimal('montoTharpa', 10, 2)->nullable();
            $table->decimal('montoTienda', 10, 2)->nullable();
            $table->decimal('montoApagar', 10, 2);
            $table->enum('pago', ['Saldado', 'Pendiente', 'Parcial']);
            $table->boolean('online');
            $table->timestamps();
            $table->foreignId('guest_user_id')->nullable()->constrained('guest_users')->cascadeOnUpdate()->nullOnDelete();

            $table->index(['clase_id', 'usuario_id']);
            $table->index('pago');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscripciones_clases');
    }
};

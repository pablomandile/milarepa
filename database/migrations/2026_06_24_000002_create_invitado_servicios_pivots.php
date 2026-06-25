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
        Schema::create('invitado_comida', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitado_id')->constrained('invitados')->cascadeOnDelete();
            $table->foreignId('comida_id')->constrained('comidas')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['invitado_id', 'comida_id']);
        });

        Schema::create('invitado_transporte', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitado_id')->constrained('invitados')->cascadeOnDelete();
            $table->foreignId('transporte_id')->constrained('transportes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['invitado_id', 'transporte_id']);
        });

        Schema::create('invitado_hospedaje', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitado_id')->constrained('invitados')->cascadeOnDelete();
            $table->foreignId('hospedaje_id')->constrained('hospedajes')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['invitado_id', 'hospedaje_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invitado_hospedaje');
        Schema::dropIfExists('invitado_transporte');
        Schema::dropIfExists('invitado_comida');
    }
};

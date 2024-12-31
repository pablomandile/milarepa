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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('asunto', 100);
            $table->string('descripcion');
            $table->date('fecha_apertura');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('estadoticket_id');
            $table->unsignedBigInteger('responsable_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};

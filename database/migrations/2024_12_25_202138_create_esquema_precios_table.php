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
        Schema::create('esquema_precios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 30);
            $table->unsignedBigInteger('membresia_id')->nullable();
            $table->foreign('membresia_id')->references('id')->on('membresias')->onDelete('cascade');
            $table->float('precio');
            $table->unsignedBigInteger('moneda_id')->nullable();
            $table->foreign('moneda_id')->references('id')->on('monedas')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esquema_precios');
    }
};

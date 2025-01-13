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
        Schema::create('esquema_descuento_membresias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('esquema_descuento_id')->constrained()->onDelete('cascade');
            $table->foreignId('membresia_id')->constrained()->onDelete('cascade');
            $table->foreignId('moneda_id')->constrained()->onDelete('restrict');
            $table->decimal('precio', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esquema_descuento_membresias');
    }
};

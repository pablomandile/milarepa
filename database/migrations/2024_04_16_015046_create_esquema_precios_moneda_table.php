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
        Schema::create('esquema_precios_moneda', function (Blueprint $table) {
            $table->unsignedInteger('esquemaPrecios_id');
            $table->unsignedInteger('moneda_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esquema_precios_moneda');
    }
};

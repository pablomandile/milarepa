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
        Schema::create('esquema_descuentos_membresia', function (Blueprint $table) {
            $table->unsignedInteger('esquemaDescuentos_id');
            $table->unsignedInteger('membresia_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('esquema_descuentos_membresia');
    }
};

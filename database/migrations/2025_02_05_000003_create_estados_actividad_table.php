<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estados_actividad', function (Blueprint $table) {
            $table->id();
            $table->string('estado', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estados_actividad');
    }
};

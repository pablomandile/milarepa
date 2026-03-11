<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paginas_actividades_online', function (Blueprint $table) {
            $table->id();
            $table->string('mes_referencia', 7);
            $table->foreignId('imagen_id')
                ->nullable()
                ->constrained('imagenes')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paginas_actividades_online');
    }
};


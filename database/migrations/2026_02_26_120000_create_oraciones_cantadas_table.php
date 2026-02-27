<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oraciones_cantadas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion');
            $table->unsignedTinyInteger('dia')->nullable();
            $table->json('dias_semana')->nullable();
            $table->time('hora');
            $table->string('periodicidad', 20);
            $table->string('imagen')->nullable();
            $table->boolean('mostrar_en_calendario')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oraciones_cantadas');
    }
};

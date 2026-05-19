<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frases_de_dharma', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('numero')->index();
            $table->text('cita_textual');
            $table->string('libro', 200);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frases_de_dharma');
    }
};

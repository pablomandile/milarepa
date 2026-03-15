<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('envios_mails', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->enum('tipo', ['Automático', 'Manual']);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('destinatario');
            $table->string('motivo');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('envios_mails');
    }
};

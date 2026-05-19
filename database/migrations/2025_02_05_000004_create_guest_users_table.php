<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('telefono')->nullable();
            $table->string('whatsapp')->nullable();
            $table->foreignId('pais_id')->nullable()->constrained('paises');
            $table->unsignedBigInteger('provincia_id')->nullable();
            $table->unsignedBigInteger('municipio_id')->nullable();
            $table->unsignedBigInteger('barrio_id')->nullable();
            $table->string('direccion')->nullable();
            $table->boolean('msgxmail')->default(false);
            $table->boolean('msgxwapp')->default(false);
            $table->boolean('accesibilidad')->default(false);
            $table->string('accesibilidad_desc')->nullable();
            $table->boolean('info_tarjetas_kadampa')->default(false);
            $table->dateTime('envioInfoTk')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_users');
    }
};

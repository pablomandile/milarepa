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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->foreignId('current_team_id')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
            $table->timestamps();
            $table->boolean('accesibilidad')->default(false);
            $table->string('accesibilidad_desc')->nullable();
            $table->string('direccion')->nullable();
            $table->unsignedBigInteger('pais_id')->nullable()->default(1);
            $table->unsignedBigInteger('provincia_id')->nullable();
            $table->unsignedBigInteger('municipio_id')->nullable();
            $table->unsignedBigInteger('barrio_id')->nullable();
            $table->string('telefono', 100)->nullable();
            $table->string('whatsapp', 100)->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->unsignedBigInteger('sexo_id')->nullable();
            $table->boolean('es_maestro')->default(false);
            $table->boolean('es_coordinador')->default(false);
            $table->boolean('perfil_completo')->default(false);
            $table->boolean('msgxmail')->default(false);
            $table->boolean('msgxwapp')->default(false);
            $table->foreignId('programa_estudio_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

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
        Schema::create('entidades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80);
            $table->text('descripcion')->max(600)->nullable();
            $table->string('abreviacion',10)->nullable();
            $table->string('direccion', 255.)->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('telefono2', 50)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('web_uri', 255)->nullable();
            $table->string('instagram_uri', 255)->nullable();
            $table->string('facebook_uri', 255)->nullable();
            $table->string('twitter_uri', 255)->nullable();
            $table->string('youtube_uri', 255)->nullable();
            $table->string('logo_uri', 255)->nullable();
            $table->string('email1',50)->nullable();
            $table->string('email2',50)->nullable();
            $table->boolean('entidad_principal');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entidades');
    }
};

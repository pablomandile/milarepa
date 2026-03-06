<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lugares', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 200)->unique();
            $table->text('descripcion');
            $table->string('direccion', 255);
            $table->string('telefono', 50);
            $table->string('whatsapp', 30)->nullable();
            $table->string('web_uri', 255)->nullable();
            $table->string('instagram_uri', 255)->nullable();
            $table->string('logo_uri', 255)->nullable();
            $table->string('email1', 50)->nullable();
            $table->string('email2', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lugares');
    }
};

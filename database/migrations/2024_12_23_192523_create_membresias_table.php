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
        Schema::create('membresias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 20);
            $table->decimal('valor', 10, 2)->default(0);
            $table->string('descripcion', 150)->nullable();
            $table->longText('info')->nullable();
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->foreign('entidad_id')->references('id')->on('entidades')->onDelete('cascade');
            $table->foreignId('botonpago_id')->nullable();
            $table->foreignId('imagen_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membresias');
    }
};

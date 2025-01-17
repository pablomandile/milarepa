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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('accesibilidad')->default(false);
            $table->string('accesibilidad_desc')->nullable();
            $table->string('direccion')->nullable(); 

            $table->unsignedBigInteger('pais_id')->nullable()->default(1);
            $table->foreign('pais_id')->references('id')
            ->on('paises')->onDelete('cascade'); 
            
            $table->unsignedBigInteger('localidad_id')->nullable();
            $table->foreign('localidad_id')->references('id')
            ->on('localidades')->onDelete('cascade');

            $table->string('telefono', 100)->nullable();
            $table->string('whatsapp', 100)->nullable();
            $table->string('edad', 3)->nullable();

            $table->unsignedBigInteger('sexo_id')->nullable();
            $table->foreign('sexo_id')->references('id')
            ->on('sexos')->onDelete('cascade');

            $table->unsignedBigInteger('membresia_id')->nullable();
            $table->foreign('membresia_id')->references('id')
            ->on('membresias')->onDelete('cascade');

            $table->boolean('es_maestro')->default(false);
            $table->boolean('es_coordinador')->default(false);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['accesibilidad', 'accesibilidad_desc', 'direccion']);
        });
    }
};

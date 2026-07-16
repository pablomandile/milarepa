<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega el formato del libro (Físico / Ebook / Audiolibro). Las filas
     * existentes quedan como 'Físico' por el default.
     */
    public function up(): void
    {
        Schema::table('libros', function (Blueprint $table) {
            $table->string('tipo', 20)->default('Físico')->after('editorial');
        });
    }

    public function down(): void
    {
        Schema::table('libros', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};

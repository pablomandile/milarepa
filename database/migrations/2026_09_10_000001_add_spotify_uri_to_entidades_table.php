<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `spotify_uri` está en el $fillable de Entidad y se usa en el footer desde hace
 * rato, pero nunca tuvo migración: en producción la columna se agregó a mano. Esta
 * migración cierra esa deriva para que una instalación nueva (otro centro) tenga la
 * columna sin depender de un ALTER manual. Guardada con hasColumn: en las bases que
 * ya la tienen es un no-op.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('entidades', 'spotify_uri')) {
            return;
        }

        Schema::table('entidades', function (Blueprint $table) {
            $table->string('spotify_uri', 255)->nullable()->after('youtube_uri');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('entidades', 'spotify_uri')) {
            return;
        }

        Schema::table('entidades', function (Blueprint $table) {
            $table->dropColumn('spotify_uri');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * El ciclo de una clase pasa a ser opcional: `clases.ciclo_id` se hace nullable.
 * Se conserva el mismo comportamiento de FK (cascadeOnUpdate + restrictOnDelete).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clases', function (Blueprint $table) {
            $table->dropForeign(['ciclo_id']);
        });

        DB::statement('ALTER TABLE `clases` MODIFY `ciclo_id` BIGINT UNSIGNED NULL');

        Schema::table('clases', function (Blueprint $table) {
            $table->foreign('ciclo_id')->references('id')->on('ciclos')
                ->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clases', function (Blueprint $table) {
            $table->dropForeign(['ciclo_id']);
        });

        // Nota: revertir a NOT NULL falla si existen clases con ciclo_id nulo.
        DB::statement('ALTER TABLE `clases` MODIFY `ciclo_id` BIGINT UNSIGNED NOT NULL');

        Schema::table('clases', function (Blueprint $table) {
            $table->foreign('ciclo_id')->references('id')->on('ciclos')
                ->cascadeOnUpdate()->restrictOnDelete();
        });
    }
};

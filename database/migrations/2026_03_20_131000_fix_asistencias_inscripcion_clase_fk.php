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
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropForeign(['inscripcion_clase_id']);
            $table->foreign('inscripcion_clase_id')
                ->references('id')
                ->on('inscripciones_clases')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropForeign(['inscripcion_clase_id']);
            $table->foreign('inscripcion_clase_id')
                ->references('id')
                ->on('clases')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }
};

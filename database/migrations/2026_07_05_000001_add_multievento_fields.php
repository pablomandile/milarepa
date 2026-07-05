<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Campos usados por la importación "multievento":
 * - inscripciones.confirmado_manual: preconfirmación de asistencia (eventos con cupo).
 * - users.medio_comunicacion: "¿dónde nos conociste?" (se carga una sola vez, al crear el user).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->boolean('confirmado_manual')->nullable()->default(false)->after('asistencia');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('medio_comunicacion')->nullable()->after('whatsapp');
        });
    }

    public function down(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropColumn('confirmado_manual');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('medio_comunicacion');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->enum('estado', ['Registrada', 'Confirmada'])
                ->default('Registrada')
                ->after('pago');
        });

        DB::table('inscripciones')
            ->whereNull('estado')
            ->update(['estado' => 'Registrada']);

        if (Schema::hasColumn('inscripciones', 'estado_id')) {
            try {
                Schema::table('inscripciones', function (Blueprint $table) {
                    $table->dropForeign(['estado_id']);
                });
            } catch (\Throwable $e) {
                // Si no existe la FK con ese nombre convención, continuar y eliminar la columna.
            }

            Schema::table('inscripciones', function (Blueprint $table) {
                $table->dropColumn('estado_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            if (!Schema::hasColumn('inscripciones', 'estado_id')) {
                $table->unsignedBigInteger('estado_id')->nullable()->after('pago');
            }
            $table->dropColumn('estado');
        });
    }
};

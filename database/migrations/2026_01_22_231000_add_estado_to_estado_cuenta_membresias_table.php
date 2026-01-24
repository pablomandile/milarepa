<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('estado_cuenta_membresias', function (Blueprint $table) {
            if (!Schema::hasColumn('estado_cuenta_membresias', 'estado')) {
                $table->string('estado', 20)->default('Activa')->after('pagado');
            }
            // Helpful index for monthly lookups
            $table->index(['user_id', 'membresia_id', 'mes_pagado'], 'ecm_user_membresia_mes_idx');
        });
    }

    public function down(): void
    {
        Schema::table('estado_cuenta_membresias', function (Blueprint $table) {
            if (Schema::hasColumn('estado_cuenta_membresias', 'estado')) {
                $table->dropColumn('estado');
            }
            $table->dropIndex('ecm_user_membresia_mes_idx');
        });
    }
};

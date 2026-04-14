<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('oraciones_cantadas') || Schema::hasColumn('oraciones_cantadas', 'configuracion_por_mes')) {
            return;
        }

        Schema::table('oraciones_cantadas', function (Blueprint $table) {
            $table->json('configuracion_por_mes')->nullable()->after('periodicidad');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('oraciones_cantadas') || !Schema::hasColumn('oraciones_cantadas', 'configuracion_por_mes')) {
            return;
        }

        Schema::table('oraciones_cantadas', function (Blueprint $table) {
            $table->dropColumn('configuracion_por_mes');
        });
    }
};

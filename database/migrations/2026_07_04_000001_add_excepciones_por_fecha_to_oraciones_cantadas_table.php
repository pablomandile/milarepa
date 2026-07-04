<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oraciones_cantadas', function (Blueprint $table) {
            $table->json('excepciones_por_fecha')->nullable()->after('configuracion_por_mes');
        });
    }

    public function down(): void
    {
        Schema::table('oraciones_cantadas', function (Blueprint $table) {
            $table->dropColumn('excepciones_por_fecha');
        });
    }
};

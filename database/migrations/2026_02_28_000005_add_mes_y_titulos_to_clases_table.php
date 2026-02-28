<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clases', function (Blueprint $table) {
            $table->string('mes_referencia', 7)->nullable()->after('ciclo_id');
            $table->json('titulos_por_fecha')->nullable()->after('dias_semana');
        });
    }

    public function down(): void
    {
        Schema::table('clases', function (Blueprint $table) {
            $table->dropColumn(['mes_referencia', 'titulos_por_fecha']);
        });
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oraciones_cantadas', function (Blueprint $table) {
            $table->json('horarios_por_dia')->nullable()->after('hora');
        });
    }

    public function down(): void
    {
        Schema::table('oraciones_cantadas', function (Blueprint $table) {
            $table->dropColumn('horarios_por_dia');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Promoción 2x1 ("van dos, paga uno"): al inscribirse se exige un acompañante y
 * el primero no paga la actividad (sí los servicios). Ver BUSINESS_RULES.md §2.6.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->boolean('promo_2x1')->default(false)->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->dropColumn('promo_2x1');
        });
    }
};

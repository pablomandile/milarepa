<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividad_hospedaje', function (Blueprint $table) {
            // Cupo de esa acomodación para esa actividad. null = ilimitado (sin control).
            $table->unsignedInteger('cantidad')->nullable()->after('hospedaje_id');
        });
    }

    public function down(): void
    {
        Schema::table('actividad_hospedaje', function (Blueprint $table) {
            $table->dropColumn('cantidad');
        });
    }
};

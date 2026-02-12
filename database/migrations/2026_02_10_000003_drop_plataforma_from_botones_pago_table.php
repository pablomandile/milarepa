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
        Schema::table('botones_pago', function (Blueprint $table) {
            if (Schema::hasColumn('botones_pago', 'plataforma')) {
                $table->dropColumn('plataforma');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('botones_pago', function (Blueprint $table) {
            $table->string('plataforma')->nullable();
        });
    }
};

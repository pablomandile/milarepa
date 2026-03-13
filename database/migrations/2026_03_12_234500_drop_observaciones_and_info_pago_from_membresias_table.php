<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membresias', function (Blueprint $table) {
            if (Schema::hasColumn('membresias', 'observaciones')) {
                $table->dropColumn('observaciones');
            }

            if (Schema::hasColumn('membresias', 'info_pago')) {
                $table->dropColumn('info_pago');
            }
        });
    }

    public function down(): void
    {
        Schema::table('membresias', function (Blueprint $table) {
            if (!Schema::hasColumn('membresias', 'observaciones')) {
                $table->longText('observaciones')->nullable()->after('info');
            }

            if (!Schema::hasColumn('membresias', 'info_pago')) {
                $table->string('info_pago')->nullable()->after('observaciones');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estado_cuenta_membresias', function (Blueprint $table) {
            if (!Schema::hasColumn('estado_cuenta_membresias', 'info_pago')) {
                $table->string('info_pago')->nullable()->after('observaciones');
            }
        });
    }

    public function down(): void
    {
        Schema::table('estado_cuenta_membresias', function (Blueprint $table) {
            if (Schema::hasColumn('estado_cuenta_membresias', 'info_pago')) {
                $table->dropColumn('info_pago');
            }
        });
    }
};

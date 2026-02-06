<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estado_cuenta_membresias', function (Blueprint $table) {
            $table->string('comprobante', 255)->nullable()->after('modo');
        });
    }

    public function down(): void
    {
        Schema::table('estado_cuenta_membresias', function (Blueprint $table) {
            $table->dropColumn('comprobante');
        });
    }
};

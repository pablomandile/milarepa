<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('estado_cuenta_membresias', function (Blueprint $table) {
            if (!Schema::hasColumn('estado_cuenta_membresias', 'modo')) {
                $table->string('modo', 50)->nullable()->after('estado');
            }
        });
    }

    public function down(): void
    {
        Schema::table('estado_cuenta_membresias', function (Blueprint $table) {
            if (Schema::hasColumn('estado_cuenta_membresias', 'modo')) {
                $table->dropColumn('modo');
            }
        });
    }
};

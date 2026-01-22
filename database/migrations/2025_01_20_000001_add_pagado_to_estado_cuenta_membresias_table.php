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
        Schema::table('estado_cuenta_membresias', function (Blueprint $table) {
            if (!Schema::hasColumn('estado_cuenta_membresias', 'pagado')) {
                $table->boolean('pagado')->default(false)->after('observaciones');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('estado_cuenta_membresias', function (Blueprint $table) {
            $table->dropColumn('pagado');
        });
    }
};

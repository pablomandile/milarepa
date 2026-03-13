<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estado_cuenta_membresias', function (Blueprint $table) {
            if (!Schema::hasColumn('estado_cuenta_membresias', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('estado_cuenta_membresias', function (Blueprint $table) {
            if (Schema::hasColumn('estado_cuenta_membresias', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};

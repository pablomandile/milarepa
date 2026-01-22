<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'localidad_id')) {
                try {
                    $table->dropConstrainedForeignId('localidad_id');
                } catch (\Throwable $e) {
                    $table->dropColumn('localidad_id');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'localidad_id')) {
                $table->unsignedBigInteger('localidad_id')->nullable();
                // No FK re-added to avoid mismatch; add manually if required
            }
        });
    }
};

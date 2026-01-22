<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'municipio_id')) {
                $table->unsignedBigInteger('municipio_id')->nullable()->after('provincia_id');
            }
            if (!Schema::hasColumn('users', 'barrio_id')) {
                $table->unsignedBigInteger('barrio_id')->nullable()->after('municipio_id');
            }
            // Skip FKs to avoid type mismatch across environments
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $drops = [];
            if (Schema::hasColumn('users', 'municipio_id')) {
                $drops[] = 'municipio_id';
            }
            if (Schema::hasColumn('users', 'barrio_id')) {
                $drops[] = 'barrio_id';
            }
            if (!empty($drops)) {
                $table->dropColumn($drops);
            }
        });
    }
};

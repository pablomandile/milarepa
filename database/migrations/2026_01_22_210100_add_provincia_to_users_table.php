<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add provincia_id after pais_id for logical order, guard if exists
            if (!Schema::hasColumn('users', 'provincia_id')) {
                $table->unsignedBigInteger('provincia_id')->nullable()->after('pais_id');
            }
            // Skip FK to avoid type mismatch across environments
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'provincia_id')) {
                $table->dropColumn('provincia_id');
            }
        });
    }
};

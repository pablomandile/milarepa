<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ciclos', function (Blueprint $table) {
            if (!Schema::hasColumn('ciclos', 'mes')) {
                $table->unsignedTinyInteger('mes')->nullable()->after('nombre');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ciclos', function (Blueprint $table) {
            if (Schema::hasColumn('ciclos', 'mes')) {
                $table->dropColumn('mes');
            }
        });
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->foreignId('lugar_id')
                ->nullable()
                ->after('entidad_id')
                ->constrained('lugares')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('actividades', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lugar_id');
        });
    }
};


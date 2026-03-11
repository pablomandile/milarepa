<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oraciones_cantadas', function (Blueprint $table) {
            $table->foreignId('modalidad_id')
                ->nullable()
                ->after('periodicidad')
                ->constrained('modalidades')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('oraciones_cantadas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('modalidad_id');
        });
    }
};

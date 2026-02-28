<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clases', function (Blueprint $table) {
            $table->foreignId('entidad_id')
                ->nullable()
                ->after('ciclo_id')
                ->constrained('entidades')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('clases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('entidad_id');
        });
    }
};


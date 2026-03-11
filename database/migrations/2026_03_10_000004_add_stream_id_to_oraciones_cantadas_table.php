<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oraciones_cantadas', function (Blueprint $table) {
            $table->foreignId('stream_id')
                ->nullable()
                ->after('periodicidad')
                ->constrained('streams')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('oraciones_cantadas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stream_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'programa_estudio_id')) {
                $table->foreignId('programa_estudio_id')
                    ->nullable()
                    ->constrained('programa_estudios')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop FK with constraint if present, else drop column
            try {
                $table->dropConstrainedForeignId('programa_estudio_id');
            } catch (\Throwable $e) {
                if (Schema::hasColumn('users', 'programa_estudio_id')) {
                    $table->dropColumn('programa_estudio_id');
                }
            }
        });
    }
};

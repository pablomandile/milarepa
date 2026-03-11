<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membresias', function (Blueprint $table) {
            if (!Schema::hasColumn('membresias', 'imagen_id')) {
                $table->foreignId('imagen_id')
                    ->nullable()
                    ->after('botonpago_id')
                    ->constrained('imagenes')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('membresias', function (Blueprint $table) {
            if (Schema::hasColumn('membresias', 'imagen_id')) {
                $table->dropConstrainedForeignId('imagen_id');
            }
        });
    }
};


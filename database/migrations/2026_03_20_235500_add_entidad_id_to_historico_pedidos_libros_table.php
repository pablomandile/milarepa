<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('historico_pedidos_libros', function (Blueprint $table) {
            $table->foreignId('entidad_id')
                ->nullable()
                ->after('libro_id')
                ->constrained('entidades')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->index('entidad_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historico_pedidos_libros', function (Blueprint $table) {
            $table->dropConstrainedForeignId('entidad_id');
        });
    }
};

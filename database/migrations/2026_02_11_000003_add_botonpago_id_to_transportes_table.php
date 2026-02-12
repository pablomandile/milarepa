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
        Schema::table('transportes', function (Blueprint $table) {
            $table->foreignId('botonpago_id')
                ->nullable()
                ->after('descripcion')
                ->constrained('botones_pago')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transportes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('botonpago_id');
        });
    }
};

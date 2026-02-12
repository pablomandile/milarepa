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
        Schema::table('botones_pago', function (Blueprint $table) {
            $table->foreignId('metodo_pago_id')
                ->nullable()
                ->after('link')
                ->constrained('metodos_pago')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('botones_pago', function (Blueprint $table) {
            $table->dropConstrainedForeignId('metodo_pago_id');
        });
    }
};

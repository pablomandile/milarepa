<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mismo modelo de total dividido que inscripciones: moneda_id (null = legacy /
     * moneda principal) + porción del subtotal del invitado en la moneda principal.
     */
    public function up(): void
    {
        Schema::table('invitados', function (Blueprint $table) {
            $table->foreignId('moneda_id')->nullable()->after('montoapagar')->constrained('monedas');
            $table->decimal('monto_moneda_principal', 10, 2)->nullable()->after('moneda_id');
        });
    }

    public function down(): void
    {
        Schema::table('invitados', function (Blueprint $table) {
            $table->dropConstrainedForeignId('moneda_id');
            $table->dropColumn('monto_moneda_principal');
        });
    }
};

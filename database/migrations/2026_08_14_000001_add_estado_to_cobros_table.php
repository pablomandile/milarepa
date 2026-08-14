<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ciclo de vida del cobro: `confirmado` (plata verificada, default) | `a_revisar`
 * (comprobante informado por el usuario, pendiente de verificación del admin).
 * Los cobros a revisar NO suman en montoCobrado()/saldoPendiente().
 * Ortogonal a `origen` (procedencia: manual, checkout, mercadopago, ...).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cobros', function (Blueprint $table) {
            $table->string('estado', 20)->default('confirmado')->after('origen');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::table('cobros', function (Blueprint $table) {
            $table->dropIndex(['estado']);
            $table->dropColumn('estado');
        });
    }
};

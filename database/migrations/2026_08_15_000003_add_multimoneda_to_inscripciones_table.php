<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * - montoHospedaje: desglose que faltaba (hoy el hospedaje del titular solo
     *   entra sumado en montoapagar; invitados ya lo tienen).
     * - moneda_id: moneda en la que se cotizó la inscripción. null = legacy
     *   (equivale a la moneda principal). No se backfillea.
     * - monto_moneda_principal: porción del total cobrada en la moneda principal
     *   cuando la inscripción es en otra moneda y algún servicio no tiene precio
     *   en ella (modelo de "total dividido"). null = sin porción en principal.
     */
    public function up(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->decimal('montoHospedaje', 10, 2)->nullable()->after('montoComidas');
            $table->foreignId('moneda_id')->nullable()->after('montoapagar')->constrained('monedas');
            $table->decimal('monto_moneda_principal', 10, 2)->nullable()->after('moneda_id');
        });
    }

    public function down(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('moneda_id');
            $table->dropColumn(['montoHospedaje', 'monto_moneda_principal']);
        });
    }
};

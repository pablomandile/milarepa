<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ¿El `monto` de este cobro lo declaró una persona, o lo puso el sistema de oficio?
 *
 * Un cobro `a_revisar` nace cuando alguien sube un comprobante, y hasta ahora su
 * monto era siempre el SALDO PENDIENTE entero: una seña de $ 5.000 sobre una
 * actividad de $ 30.000 se mostraba como "Informado a revisar: $ 30.000", que es
 * justo el número que el admin mira para decidir. Con este flag el importe que la
 * persona declara se respeta (y se suma si informa un segundo pago), mientras que
 * el provisional se sigue recalculando contra el saldo.
 *
 * `false` para todo lo existente: nada cambia de comportamiento para lo ya cargado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cobros', function (Blueprint $table) {
            $table->boolean('monto_declarado')->default(false)->after('monto');
        });
    }

    public function down(): void
    {
        Schema::table('cobros', function (Blueprint $table) {
            $table->dropColumn('monto_declarado');
        });
    }
};

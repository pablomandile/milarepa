<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * POS multi-moneda. Regla de negocio: lo único que puede venderse en una moneda
 * distinta de la principal es una **actividad**; libros, productos, artículos de
 * tienda e inscripciones a clases van siempre en pesos. Cada moneda se muestra y
 * se salda por separado — no hay conversión.
 *
 * - venta_pos.total sigue siendo el total en la moneda PRINCIPAL.
 * - venta_pos.totales_por_moneda: {moneda_id: total} de las demás monedas.
 * - venta_pos.pagos_por_moneda: {moneda_id: {metodo_pago_id, comprobante_id}},
 *   porque cada moneda se cobra aparte y puede pagarse con otro medio.
 * - venta_pos_items.moneda_id + subtotal_moneda_principal: espejo de lo que ya
 *   hacen las inscripciones (BUSINESS_RULES §2.1bis). Una inscripción en dólares
 *   con servicios sin precio en dólares aporta a las dos monedas a la vez.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('venta_pos', function (Blueprint $table) {
            $table->json('totales_por_moneda')->nullable()->after('total');
            $table->json('pagos_por_moneda')->nullable()->after('totales_por_moneda');
        });

        Schema::table('venta_pos_items', function (Blueprint $table) {
            $table->foreignId('moneda_id')->nullable()->after('subtotal')->constrained('monedas');
            $table->decimal('subtotal_moneda_principal', 10, 2)->nullable()->after('moneda_id');
        });
    }

    public function down(): void
    {
        Schema::table('venta_pos_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('moneda_id');
            $table->dropColumn('subtotal_moneda_principal');
        });

        Schema::table('venta_pos', function (Blueprint $table) {
            $table->dropColumn(['totales_por_moneda', 'pagos_por_moneda']);
        });
    }
};

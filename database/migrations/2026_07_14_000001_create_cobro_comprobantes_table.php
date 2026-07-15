<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Multi-comprobante por cobro: un cobro puede tener varios comprobantes.
 * Reemplaza la FK 1:1 `cobros.comprobante_id` por la tabla hija `cobro_comprobantes`.
 * Backfill: cada `comprobante_id` existente pasa a una fila de la tabla nueva.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('cobro_comprobantes')) {
            Schema::create('cobro_comprobantes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cobro_id')->constrained('cobros')->cascadeOnDelete();
                $table->foreignId('imagen_id')->nullable()->constrained('imagenes')->nullOnDelete();
                $table->string('descripcion')->nullable();
                $table->timestamps();
            });
        }

        // Backfill: 1 fila por cada cobro que hoy tiene comprobante (incluye soft-deleted).
        if (Schema::hasColumn('cobros', 'comprobante_id')) {
            DB::statement(
                'INSERT INTO cobro_comprobantes (cobro_id, imagen_id, created_at, updated_at)
                 SELECT id, comprobante_id, NOW(), NOW() FROM cobros WHERE comprobante_id IS NOT NULL'
            );

            Schema::table('cobros', function (Blueprint $table) {
                $table->dropForeign(['comprobante_id']);
                $table->dropColumn('comprobante_id');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('cobros', 'comprobante_id')) {
            Schema::table('cobros', function (Blueprint $table) {
                $table->foreignId('comprobante_id')->nullable()->after('referencia')->constrained('imagenes')->nullOnDelete();
            });
        }

        // Restaura 1 comprobante por cobro (el de menor id) en la columna vieja.
        DB::statement(
            'UPDATE cobros c
             SET comprobante_id = (
                 SELECT cc.imagen_id FROM cobro_comprobantes cc
                 WHERE cc.cobro_id = c.id AND cc.imagen_id IS NOT NULL
                 ORDER BY cc.id ASC LIMIT 1
             )'
        );

        Schema::dropIfExists('cobro_comprobantes');
    }
};

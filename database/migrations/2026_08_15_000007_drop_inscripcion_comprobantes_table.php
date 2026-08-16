<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 2 de "cobros a revisar": se va el staging `inscripcion_comprobantes`.
 *
 * Desde la fase 1 nunca hay comprobante sin cobro: subir uno crea un cobro
 * `a_revisar` con el comprobante enlazado en `cobro_comprobantes`. Esta tabla
 * dejó de recibir escrituras y su contenido se pasó al ledger con
 * `cobros:migrar-staging` (verificado en producción antes del drop: sus 3 filas
 * ya estaban en `cobro_comprobantes`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('inscripcion_comprobantes');
    }

    /**
     * Recrea la tabla vacía con su forma final (la de
     * 2026_07_11_000002_add_imagen_id_to_inscripcion_comprobantes). Los datos no
     * vuelven: viven en cobro_comprobantes.
     */
    public function down(): void
    {
        Schema::create('inscripcion_comprobantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscripcion_id')->constrained('inscripciones')->cascadeOnDelete();
            $table->string('ruta')->nullable();
            $table->foreignId('imagen_id')->nullable()->constrained('imagenes')->nullOnDelete();
            $table->timestamps();
        });
    }
};

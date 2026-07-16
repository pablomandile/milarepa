<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Header del ticket del Punto de Venta. Agrupa las líneas (venta_pos_items) y
     * su total = suma de subtotales = suma de los cobros del ledger.
     */
    public function up(): void
    {
        Schema::create('venta_pos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->foreignId('vendedor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cliente_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('entidad_id')->nullable()->constrained('entidades')->nullOnDelete();
            $table->foreignId('metodo_pago_id')->nullable()->constrained('metodos_pago')->nullOnDelete();
            $table->foreignId('comprobante_id')->nullable()->constrained('imagenes')->nullOnDelete();
            $table->decimal('total', 10, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->uuid('idempotency_key')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_pos');
    }
};

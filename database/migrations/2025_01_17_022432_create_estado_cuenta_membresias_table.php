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
        Schema::create('estado_cuenta_membresias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('membresia_id');
            $table->foreign('membresia_id')->references('id')->on('membresias')->onDelete('cascade');
            $table->date('fecha_pago')->nullable();
            $table->string('mes_pagado');
            $table->decimal('importe');
            $table->string('observaciones');
            $table->string('info_pago')->nullable();
            $table->boolean('pagado')->default(false);
            $table->string('estado', 20)->default('Activa');
            $table->enum('modo', [
                'Efectivo',
                'Transferencia',
                'Suscripción',
                'Tarjeta Crédito',
                'Tarjeta Débito',
                'Otro',
            ])->nullable();
            $table->string('comprobante', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['user_id', 'membresia_id', 'mes_pagado'], 'ecm_user_membresia_mes_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estado_cuenta_membresias');
    }
};

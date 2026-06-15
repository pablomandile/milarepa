<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->date('fecha_pago')->nullable()->after('pago');
            $table->string('referencia_pago')->nullable()->after('fecha_pago');
            $table->text('observaciones')->nullable()->after('referencia_pago');
        });
    }

    public function down(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropColumn(['fecha_pago', 'referencia_pago', 'observaciones']);
        });
    }
};

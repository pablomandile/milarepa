<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->timestamp('auditoria_fecha')->nullable()->after('transporte_id');
            $table->foreignId('auditor')->nullable()->after('auditoria_fecha')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropForeign(['auditor']);
            $table->dropColumn(['auditoria_fecha', 'auditor']);
        });
    }
};

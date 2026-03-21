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
        Schema::table('historico_pedidos_libros', function (Blueprint $table) {
            $table->unsignedInteger('cantidad_total')->default(0)->after('entidad_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historico_pedidos_libros', function (Blueprint $table) {
            $table->dropColumn('cantidad_total');
        });
    }
};

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
        Schema::table('inscripciones_clases', function (Blueprint $table) {
            if (!Schema::hasColumn('inscripciones_clases', 'articulos_tienda')) {
                $table->text('articulos_tienda')->nullable()->after('montoTienda');
            }

            if (!Schema::hasColumn('inscripciones_clases', 'articulos_tharpa')) {
                $table->text('articulos_tharpa')->nullable()->after('articulos_tienda');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inscripciones_clases', function (Blueprint $table) {
            if (Schema::hasColumn('inscripciones_clases', 'articulos_tharpa')) {
                $table->dropColumn('articulos_tharpa');
            }

            if (Schema::hasColumn('inscripciones_clases', 'articulos_tienda')) {
                $table->dropColumn('articulos_tienda');
            }
        });
    }
};

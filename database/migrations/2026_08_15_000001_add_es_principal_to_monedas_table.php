<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marca cuál es la moneda principal del sistema (la de los precios "planos"
     * de servicios y de todo lo legacy). Debe existir exactamente una.
     */
    public function up(): void
    {
        Schema::table('monedas', function (Blueprint $table) {
            $table->boolean('es_principal')->default(false)->after('simbolo');
        });

        // Backfill: en producción la principal es "Pesos Argentinos"; si no existe
        // (bases de dev/test), se marca la moneda activa de menor id.
        $afectadas = DB::table('monedas')->where('nombre', 'Pesos Argentinos')->update(['es_principal' => true]);
        if ($afectadas === 0) {
            DB::table('monedas')->whereNull('deleted_at')->orderBy('id')->limit(1)->update(['es_principal' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('monedas', function (Blueprint $table) {
            $table->dropColumn('es_principal');
        });
    }
};

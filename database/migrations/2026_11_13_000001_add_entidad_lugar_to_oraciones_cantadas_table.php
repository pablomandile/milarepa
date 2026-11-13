<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Entidad (sede) y Lugar de cada oración cantada, como en Clases: sin lugar se
 * muestra la dirección de la entidad. Las oraciones que ya existían quedan en
 * la entidad principal, así no aparecen sin sede en la página pública.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oraciones_cantadas', function (Blueprint $table) {
            $table->foreignId('entidad_id')->nullable()->after('descripcion')
                ->constrained('entidades')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignId('lugar_id')->nullable()->after('entidad_id')
                ->constrained('lugares')->nullOnDelete();
        });

        $entidadPrincipalId = DB::table('entidades')->where('entidad_principal', true)->orderBy('id')->value('id');

        if ($entidadPrincipalId) {
            DB::table('oraciones_cantadas')->whereNull('entidad_id')->update(['entidad_id' => $entidadPrincipalId]);
        }
    }

    public function down(): void
    {
        Schema::table('oraciones_cantadas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lugar_id');
            $table->dropConstrainedForeignId('entidad_id');
        });
    }
};

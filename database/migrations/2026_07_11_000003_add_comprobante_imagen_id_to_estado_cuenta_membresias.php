<?php

use App\Models\Imagen;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Deprecación del comprobante como path suelto en membresías:
 * `estado_cuenta_membresias.comprobante` (string) pasa a `comprobante_imagen_id` (FK a `imagenes`).
 * Backfill: cada ruta histórica se materializa como fila `imagenes` (firstOrCreate por ruta).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('estado_cuenta_membresias', 'comprobante_imagen_id')) {
            Schema::table('estado_cuenta_membresias', function (Blueprint $table) {
                $table->foreignId('comprobante_imagen_id')->nullable()->after('comprobante')
                    ->constrained('imagenes')->nullOnDelete();
            });
        }

        if (Schema::hasColumn('estado_cuenta_membresias', 'comprobante')) {
            DB::table('estado_cuenta_membresias')->whereNotNull('comprobante')->where('comprobante', '!=', '')->get()
                ->each(function ($row) {
                    $imagen = Imagen::firstOrCreate(['ruta' => $row->comprobante], ['nombre' => basename($row->comprobante)]);
                    DB::table('estado_cuenta_membresias')->where('id', $row->id)->update(['comprobante_imagen_id' => $imagen->id]);
                });

            Schema::table('estado_cuenta_membresias', function (Blueprint $table) {
                $table->dropColumn('comprobante');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('estado_cuenta_membresias', 'comprobante')) {
            Schema::table('estado_cuenta_membresias', function (Blueprint $table) {
                $table->string('comprobante', 255)->nullable()->after('modo');
            });
        }

        DB::table('estado_cuenta_membresias')->whereNotNull('comprobante_imagen_id')->get()->each(function ($row) {
            $ruta = DB::table('imagenes')->where('id', $row->comprobante_imagen_id)->value('ruta');
            DB::table('estado_cuenta_membresias')->where('id', $row->id)->update(['comprobante' => $ruta]);
        });

        if (Schema::hasColumn('estado_cuenta_membresias', 'comprobante_imagen_id')) {
            Schema::table('estado_cuenta_membresias', function (Blueprint $table) {
                $table->dropConstrainedForeignId('comprobante_imagen_id');
            });
        }
    }
};

<?php

use App\Models\Imagen;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Deprecación del comprobante como path suelto: `inscripcion_comprobantes.ruta` (string)
 * pasa a `imagen_id` (FK a `imagenes`), unificando el almacenamiento de comprobantes.
 * Backfill: cada ruta histórica se materializa como fila `imagenes` (firstOrCreate por ruta).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('inscripcion_comprobantes', 'imagen_id')) {
            Schema::table('inscripcion_comprobantes', function (Blueprint $table) {
                $table->foreignId('imagen_id')->nullable()->after('inscripcion_id')
                    ->constrained('imagenes')->nullOnDelete();
            });
        }

        if (Schema::hasColumn('inscripcion_comprobantes', 'ruta')) {
            DB::table('inscripcion_comprobantes')->whereNotNull('ruta')->where('ruta', '!=', '')->get()
                ->each(function ($row) {
                    $imagen = Imagen::firstOrCreate(['ruta' => $row->ruta], ['nombre' => basename($row->ruta)]);
                    DB::table('inscripcion_comprobantes')->where('id', $row->id)->update(['imagen_id' => $imagen->id]);
                });

            Schema::table('inscripcion_comprobantes', function (Blueprint $table) {
                $table->dropColumn('ruta');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('inscripcion_comprobantes', 'ruta')) {
            Schema::table('inscripcion_comprobantes', function (Blueprint $table) {
                $table->string('ruta', 255)->nullable()->after('inscripcion_id');
            });
        }

        DB::table('inscripcion_comprobantes')->whereNotNull('imagen_id')->get()->each(function ($row) {
            $ruta = DB::table('imagenes')->where('id', $row->imagen_id)->value('ruta');
            DB::table('inscripcion_comprobantes')->where('id', $row->id)->update(['ruta' => $ruta]);
        });

        if (Schema::hasColumn('inscripcion_comprobantes', 'imagen_id')) {
            Schema::table('inscripcion_comprobantes', function (Blueprint $table) {
                $table->dropConstrainedForeignId('imagen_id');
            });
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->decimal('montoActividad', 10, 2)->default(0)->after('precioGeneral');
            $table->decimal('montoGrabacion', 10, 2)->nullable()->after('montoActividad');
            $table->decimal('montoTransporte', 10, 2)->nullable()->after('montoGrabacion');
            $table->decimal('montoComidas', 10, 2)->nullable()->after('montoTransporte');
        });

        DB::table('inscripciones')
            ->whereNull('montoActividad')
            ->update([
                'montoActividad' => DB::raw('COALESCE(montoapagar, 0)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->dropColumn([
                'montoActividad',
                'montoGrabacion',
                'montoTransporte',
                'montoComidas',
            ]);
        });
    }
};

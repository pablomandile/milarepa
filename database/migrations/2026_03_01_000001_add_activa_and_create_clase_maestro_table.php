<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clases', function (Blueprint $table) {
            $table->boolean('activa')->default(true)->after('mostrar_en_calendario');
        });

        Schema::create('clase_maestro', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clase_id')->constrained('clases')->cascadeOnDelete();
            $table->foreignId('maestro_id')->constrained('maestros')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['clase_id', 'maestro_id']);
        });

        $now = now();
        $rows = DB::table('clases')
            ->whereNotNull('maestro_id')
            ->get(['id', 'maestro_id']);

        if ($rows->isNotEmpty()) {
            $pivotRows = $rows->map(function ($row) use ($now) {
                return [
                    'clase_id' => $row->id,
                    'maestro_id' => $row->maestro_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();

            DB::table('clase_maestro')->insertOrIgnore($pivotRows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('clase_maestro');

        Schema::table('clases', function (Blueprint $table) {
            $table->dropColumn('activa');
        });
    }
};

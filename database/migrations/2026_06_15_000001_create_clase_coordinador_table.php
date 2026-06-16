<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clase_coordinador', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clase_id')->constrained('clases')->cascadeOnDelete();
            $table->foreignId('coordinador_id')->constrained('coordinadores')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['clase_id', 'coordinador_id']);
        });

        // Migra el coordinador único existente (columna clases.coordinador_id) al pivote.
        if (Schema::hasColumn('clases', 'coordinador_id')) {
            $now = now();
            $rows = DB::table('clases')
                ->whereNotNull('coordinador_id')
                ->get(['id', 'coordinador_id']);

            if ($rows->isNotEmpty()) {
                $pivotRows = $rows->map(function ($row) use ($now) {
                    return [
                        'clase_id' => $row->id,
                        'coordinador_id' => $row->coordinador_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                })->all();

                DB::table('clase_coordinador')->insertOrIgnore($pivotRows);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('clase_coordinador');
    }
};

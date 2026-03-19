<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('email_envio_configuraciones')->updateOrInsert(
            ['proceso_key' => 'reporte_semanal_inscripciones_actividad'],
            [
                'proceso_nombre' => 'Reporte Semanal de Inscripciones por Actividad',
                'plantilla_archivo' => 'reporte_semanal_inscripciones_actividad.blade.php',
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        DB::table('email_envio_configuraciones')
            ->where('proceso_key', 'reporte_semanal_inscripciones_actividad')
            ->delete();
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('email_plantillas')
            ->where('plantilla_archivo', 'reporte_semanal_inscripciones_actividad.blade.php')
            ->exists();

        if (!$exists) {
            DB::table('email_plantillas')->insert([
                'nombre' => 'Reporte Semanal de Inscripciones por Actividad',
                'descripcion' => 'Resumen semanal con métricas e inscripciones por actividad.',
                'plantilla_archivo' => 'reporte_semanal_inscripciones_actividad.blade.php',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('email_plantillas')
            ->where('plantilla_archivo', 'reporte_semanal_inscripciones_actividad.blade.php')
            ->delete();
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_envio_configuraciones', function (Blueprint $table) {
            $table->id();
            $table->string('proceso_key')->unique();
            $table->string('proceso_nombre');
            $table->string('plantilla_archivo');
            $table->timestamps();
        });

        $now = now();

        DB::table('email_envio_configuraciones')->insert([
            [
                'proceso_key' => 'inscripcion_registrada',
                'proceso_nombre' => 'Inscripción Registrada',
                'plantilla_archivo' => 'inscripcion_registrada.blade.php',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'proceso_key' => 'inscripcion_confirmada',
                'proceso_nombre' => 'Confirmación de Pago',
                'plantilla_archivo' => 'inscripcion_confirmada.blade.php',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'proceso_key' => 'envio_grabacion',
                'proceso_nombre' => 'Grabación Disponible',
                'plantilla_archivo' => 'envio_grabacion.blade.php',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'proceso_key' => 'informacion_membresias',
                'proceso_nombre' => 'Información de Membresías',
                'plantilla_archivo' => 'informacion_membresias.blade.php',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'proceso_key' => 'inscripcion_tk_registrada',
                'proceso_nombre' => 'Inscripción TK Registrada',
                'plantilla_archivo' => 'inscripcion_tk_registrada.blade.php',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'proceso_key' => 'envio_actividades_online',
                'proceso_nombre' => 'Actividades Online',
                'plantilla_archivo' => 'envio_Actividades_online.blade.php',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('email_envio_configuraciones');
    }
};

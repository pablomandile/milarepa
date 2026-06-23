<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Corrección puntual de datos de inscripciones (acompaña los fixes de lógica):
 *
 *  1. Inscripciones de costo 0 (incluidas en la membresía) que quedaron en
 *     'Registrada' deben estar 'Confirmada'. También cualquier inscripción que ya
 *     recibió el mail de confirmación (envioConfirmacion='Enviada') pero quedó en
 *     'Registrada'.
 *
 *  2. Inscripciones con envioGrabacion='Pendiente' cuya actividad NO ofrece
 *     grabación (actividades.grabacion_id IS NULL) deben quedar en 'No aplica'.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) estado: 0-cost saldado (o confirmación ya enviada) => Confirmada
        DB::table('inscripciones')
            ->where('estado', 'Registrada')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('pago', 'Saldado')->where('montoapagar', '<=', 0);
                })->orWhere('envioConfirmacion', 'Enviada');
            })
            ->update(['estado' => 'Confirmada']);

        // 2) envioGrabacion: Pendiente en actividades sin grabación => No aplica
        DB::table('inscripciones')
            ->where('envioGrabacion', 'Pendiente')
            ->whereIn('actividad_id', function ($q) {
                $q->select('id')->from('actividades')->whereNull('grabacion_id');
            })
            ->update(['envioGrabacion' => 'No aplica']);
    }

    public function down(): void
    {
        // Corrección de datos puntual: no es reversible.
    }
};

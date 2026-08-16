<?php

namespace App\Console\Commands;

use App\Models\Inscripcion;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Marca `envioConfirmacion = 'Enviada'` en las inscripciones de actividades
 * anteriores a una fecha de corte, para que el botón "Envío de Confirmación" no
 * dispare mails retroactivos sobre datos históricos que ya viven en la base.
 *
 * Se marcan TODAS las pendientes anteriores al corte, no sólo las que hoy calzan
 * con el criterio de envío (`montoapagar = 0` + `pago = 'Saldado'`): una
 * inscripción vieja sin saldar que mañana se concilie se volvería candidata y
 * mandaría una confirmación por una actividad de hace meses.
 *
 * Ojo con el efecto colateral: el envío de grabaciones exige
 * `envioConfirmacion = 'Enviada'`, así que el comando reporta cuántas de las
 * marcadas quedan como candidatas a recibir grabación (`envioGrabacion` en
 * 'Pendiente' y actividad con links) antes de escribir nada.
 *
 * No toca `updated_at` a propósito (usa el query builder, no Eloquent): es una
 * corrección de datos operativa, no una edición de la inscripción.
 */
class MarcarConfirmacionesHistoricas extends Command
{
    protected $signature = 'inscripciones:marcar-confirmaciones-enviadas
        {--hasta= : Fecha de corte YYYY-MM-DD; se marcan las actividades que empiezan ANTES de ese día}
        {--dry-run : Sólo reporta, no escribe}';

    protected $description = 'Marca como enviada la confirmación de las inscripciones históricas, para no mandar mails retroactivos.';

    public function handle(): int
    {
        $hasta = $this->option('hasta');
        if (!$hasta) {
            $this->error('Falta --hasta=YYYY-MM-DD. Es obligatorio: sin corte explícito se marcaría toda la base.');

            return self::FAILURE;
        }

        try {
            $corte = Carbon::parse($hasta)->startOfDay();
        } catch (\Throwable $e) {
            $this->error("Fecha inválida: {$hasta}. Usá el formato YYYY-MM-DD.");

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');

        $query = Inscripcion::query()
            ->where('envioConfirmacion', 'Pendiente')
            ->whereHas('actividad', fn ($a) => $a->where('fecha_inicio', '<', $corte));

        $total = (clone $query)->count();

        $this->info('Corte: actividades que empiezan antes de ' . $corte->toDateString());
        $this->info("Inscripciones con confirmación pendiente a marcar: {$total}");

        if ($total === 0) {
            $this->info('No hay nada que marcar.');

            return self::SUCCESS;
        }

        // Desglose, para que se vea qué se está tocando antes de tocarlo.
        foreach ((clone $query)->selectRaw('pago, count(*) c')->groupBy('pago')->get() as $fila) {
            $this->line('  pago ' . str_pad($fila->pago, 12) . $fila->c);
        }

        // Efecto colateral a vigilar: marcar la confirmación habilita el envío de
        // grabaciones, que exige envioConfirmacion = 'Enviada'.
        $habilitaGrabacion = (clone $query)
            ->where('envioGrabacion', 'Pendiente')
            ->whereHas('actividad.grabacion.linksgrabacion')
            ->count();

        if ($habilitaGrabacion > 0) {
            $this->warn("Atención: {$habilitaGrabacion} de estas quedarían habilitadas para el envío de grabaciones.");
        } else {
            $this->info('Ninguna queda habilitada para el envío de grabaciones.');
        }

        if ($dry) {
            $this->warn('DRY-RUN: no se escribió nada.');

            return self::SUCCESS;
        }

        $ids = (clone $query)->pluck('id')->all();

        // Respaldo para poder revertir: sin esto no hay forma de distinguir las que
        // marcó el comando de las que ya estaban en 'Enviada'.
        $archivo = storage_path('app/confirmaciones_marcadas_' . $corte->format('Ymd') . '_' . now()->format('YmdHis') . '.json');
        file_put_contents($archivo, json_encode([
            'corte' => $corte->toDateString(),
            'ejecutado' => now()->toDateTimeString(),
            'ids' => $ids,
        ], JSON_PRETTY_PRINT));

        $marcadas = 0;
        foreach (array_chunk($ids, 500) as $lote) {
            $marcadas += DB::table('inscripciones')
                ->whereIn('id', $lote)
                ->update(['envioConfirmacion' => 'Enviada']);
        }

        $this->info("Marcadas: {$marcadas}");
        $this->info("Respaldo de ids: {$archivo}");
        $this->line('Para revertir: volver a Pendiente los ids de ese archivo.');

        return self::SUCCESS;
    }
}

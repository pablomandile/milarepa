<?php

namespace App\Console\Commands;

use App\Models\EstadoCuentaMembresia;
use App\Models\Inscripcion;
use App\Models\InscripcionClase;
use App\Models\Venta;
use App\Services\CobroService;
use Illuminate\Console\Command;

/**
 * Backfill del ledger `cobros` a partir de los pagos históricos de cada dominio.
 * Idempotente: sólo crea cobros donde todavía no existen (ventas/actividades/clases
 * saltan si la entidad ya tiene algún cobro —import/webhook/admin—; membresías usan
 * el espejo idempotente). Los `Parcial` no se pueden reconstruir (monto real desconocido)
 * y sólo se loguean para revisión manual.
 */
class BackfillCobros extends Command
{
    protected $signature = 'cobros:backfill {--dominio=* : ventas|membresias|actividades|clases (default: todos)} {--dry-run}';

    protected $description = 'Backfill idempotente del ledger de cobros desde los pagos históricos.';

    public function handle(CobroService $cobros): int
    {
        $dominios = $this->option('dominio') ?: ['ventas', 'membresias', 'actividades', 'clases'];
        $dry = (bool) $this->option('dry-run');

        if ($dry) {
            $this->warn('DRY-RUN: no se crea nada, sólo se reportan conteos.');
        }

        foreach ($dominios as $dominio) {
            match ($dominio) {
                'ventas' => $this->backfillVentas($cobros, $dry),
                'membresias' => $this->backfillMembresias($cobros, $dry),
                'actividades' => $this->backfillInscripciones($cobros, $dry),
                'clases' => $this->backfillClases($cobros, $dry),
                default => $this->warn("Dominio desconocido: {$dominio}"),
            };
        }

        return self::SUCCESS;
    }

    private function backfillVentas(CobroService $cobros, bool $dry): void
    {
        $creados = 0;
        $omitidos = 0;

        Venta::query()->chunkById(200, function ($ventas) use ($cobros, $dry, &$creados, &$omitidos) {
            foreach ($ventas as $venta) {
                if ($venta->cobros()->exists()) {
                    $omitidos++;
                    continue;
                }
                if (!$dry) {
                    $cobros->registrar($venta, [
                        'monto' => (float) $venta->montoTotal,
                        'fecha_pago' => $venta->fecha,
                        'metodo_pago_id' => $cobros->resolverMetodoPago($venta->modo),
                        'comprobante_ids' => array_filter([$venta->comprobante_id]),
                        'registrado_por' => $venta->vendedor_id,
                        'origen' => 'backfill',
                    ], recalcular: false);
                }
                $creados++;
            }
        });

        $this->info("Ventas: {$creados} cobros" . ($dry ? ' (a crear)' : '') . ", {$omitidos} ya tenían cobro.");
    }

    private function backfillMembresias(CobroService $cobros, bool $dry): void
    {
        $procesadas = 0;

        EstadoCuentaMembresia::where('pagado', true)->chunkById(200, function ($cuotas) use ($cobros, $dry, &$procesadas) {
            foreach ($cuotas as $cuota) {
                if (!$dry) {
                    $cobros->sincronizarMembresia($cuota);
                }
                $procesadas++;
            }
        });

        $this->info("Membresías pagadas: {$procesadas} cobros espejo" . ($dry ? ' (a sincronizar)' : '') . '.');
    }

    private function backfillInscripciones(CobroService $cobros, bool $dry): void
    {
        $creados = 0;
        $omitidos = 0;
        $parciales = 0;

        Inscripcion::query()->chunkById(200, function ($inscripciones) use ($cobros, $dry, &$creados, &$omitidos, &$parciales) {
            foreach ($inscripciones as $ins) {
                if ($ins->pago === 'Parcial') {
                    $parciales++;
                    $this->line("  · Parcial a revisar: inscripción #{$ins->id} (monto real desconocido)");
                    continue;
                }
                if ($ins->pago !== 'Saldado' || (float) $ins->montoapagar <= 0) {
                    continue; // Pendiente o gratuita: sin cobro
                }
                if ($ins->cobros()->exists()) {
                    $omitidos++;
                    continue;
                }
                if (!$dry) {
                    $cobros->registrar($ins, [
                        'monto' => (float) $ins->montoapagar,
                        'fecha_pago' => $ins->fecha_pago ?: optional($ins->updated_at)->toDateString(),
                        'referencia' => $ins->referencia_pago,
                        'metodo_pago_id' => null,
                        'comprobante_ids' => $ins->comprobantes()->pluck('imagen_id')->all(),
                        'observaciones' => 'backfill: medio desconocido',
                        'origen' => 'backfill',
                    ], recalcular: false);
                }
                $creados++;
            }
        });

        $this->info("Actividades: {$creados} cobros" . ($dry ? ' (a crear)' : '') . ", {$omitidos} ya tenían cobro, {$parciales} parciales a revisar.");
    }

    private function backfillClases(CobroService $cobros, bool $dry): void
    {
        $creados = 0;
        $omitidos = 0;
        $parciales = 0;

        InscripcionClase::query()->chunkById(200, function ($inscripciones) use ($cobros, $dry, &$creados, &$omitidos, &$parciales) {
            foreach ($inscripciones as $ic) {
                if ($ic->pago === 'Parcial') {
                    $parciales++;
                    continue;
                }
                if ($ic->pago !== 'Saldado' || (float) $ic->montoApagar <= 0) {
                    continue;
                }
                if ($ic->cobros()->exists()) {
                    $omitidos++;
                    continue;
                }
                if (!$dry) {
                    $cobros->registrar($ic, [
                        'monto' => (float) $ic->montoApagar,
                        'fecha_pago' => optional($ic->updated_at)->toDateString(),
                        'metodo_pago_id' => null,
                        'observaciones' => 'backfill: medio desconocido',
                        'origen' => 'backfill',
                    ], recalcular: false);
                }
                $creados++;
            }
        });

        $this->info("Clases: {$creados} cobros" . ($dry ? ' (a crear)' : '') . ", {$omitidos} ya tenían cobro, {$parciales} parciales.");
    }
}

<?php

namespace App\Console\Commands;

use App\Models\CobroComprobante;
use App\Models\Inscripcion;
use App\Models\InscripcionComprobante;
use App\Services\CobroService;
use Illuminate\Console\Command;

/**
 * Migra los comprobantes del staging pre-cobro (`inscripcion_comprobantes`) al ledger:
 * cada imagen que no esté ya enlazada a un cobro de su inscripción pasa a un cobro
 * `a_revisar` (existente o nuevo, origen `checkout`); si la inscripción ya está saldada,
 * el comprobante documenta el cobro confirmado. Idempotente: re-correrlo no duplica.
 * La tabla staging queda congelada tras esto (drop en fase 2).
 */
class MigrarStagingComprobantes extends Command
{
    protected $signature = 'cobros:migrar-staging {--dry-run}';

    protected $description = 'Migra comprobantes del staging inscripcion_comprobantes a cobros a revisar (idempotente).';

    public function handle(CobroService $cobros): int
    {
        $dry = (bool) $this->option('dry-run');
        if ($dry) {
            $this->warn('DRY-RUN: no se escribe nada, sólo se reportan conteos.');
        }

        $inscripcionesTocadas = 0;
        $cobrosCreados = 0;
        $enlazados = 0;
        $salteados = 0;

        InscripcionComprobante::whereNotNull('imagen_id')
            ->orderBy('id')
            ->get()
            ->groupBy('inscripcion_id')
            ->each(function ($filas, $inscripcionId) use ($cobros, $dry, &$inscripcionesTocadas, &$cobrosCreados, &$enlazados, &$salteados) {
                $inscripcion = Inscripcion::find($inscripcionId);
                if (!$inscripcion) {
                    $salteados += $filas->count();

                    return;
                }

                // Imagenes ya enlazadas a algún cobro de la inscripción (incluye
                // cobros soft-deleted) — ésas no se migran de nuevo.
                $yaEnlazadas = CobroComprobante::whereIn(
                    'cobro_id',
                    $inscripcion->cobros()->withTrashed()->pluck('id')
                )->pluck('imagen_id')->all();

                $huerfanas = $filas->filter(fn ($fila) => !in_array($fila->imagen_id, $yaEnlazadas));
                $salteados += $filas->count() - $huerfanas->count();

                if ($huerfanas->isEmpty()) {
                    return;
                }

                $inscripcionesTocadas++;

                if ($dry) {
                    $enlazados += $huerfanas->count();
                    $tendriaCobroDestino = $inscripcion->cobros()->aRevisar()->exists()
                        || ($inscripcion->saldoPendiente() <= 0 && $inscripcion->cobros()->confirmados()->exists());
                    if (!$tendriaCobroDestino) {
                        $cobrosCreados++;
                    }

                    return;
                }

                foreach ($huerfanas as $fila) {
                    $cobro = $cobros->registrarComprobanteARevisar(
                        $inscripcion,
                        $fila->imagen_id,
                        $fila->descripcion,
                        origen: 'checkout',
                    );
                    if ($cobro->wasRecentlyCreated) {
                        $cobrosCreados++;
                    }
                    $enlazados++;
                }
            });

        $sufijo = $dry ? ' (a migrar)' : '';
        $this->info("Comprobantes enlazados: {$enlazados}{$sufijo} en {$inscripcionesTocadas} inscripciones ({$cobrosCreados} cobros a revisar nuevos). Salteados (ya enlazados o sin inscripción): {$salteados}.");

        return self::SUCCESS;
    }
}

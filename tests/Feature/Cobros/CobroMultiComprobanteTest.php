<?php

namespace Tests\Feature\Cobros;

use App\Models\EstadoCuentaMembresia;
use App\Models\Imagen;
use App\Models\Membresia;
use App\Models\User;
use App\Services\CobroService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Multi-comprobante por cobro (1:N): registrar acepta `comprobante_ids`,
 * y `sincronizarComprobantes` reemplaza el set filtrando nulos y duplicados.
 */
class CobroMultiComprobanteTest extends TestCase
{
    use DatabaseTransactions;

    private function cuota(): EstadoCuentaMembresia
    {
        $user = User::factory()->create();
        $membresia = Membresia::create(['nombre' => 'Multi Comp Test', 'valor' => 5000]);

        return EstadoCuentaMembresia::create([
            'user_id' => $user->id,
            'membresia_id' => $membresia->id,
            'mes_pagado' => '2026-07',
            'importe' => 5000,
            'observaciones' => '',
            'info_pago' => '',
            'pagado' => false,
            'estado' => EstadoCuentaMembresia::ESTADO_ACTIVA,
        ]);
    }

    private function imagen(string $nombre): Imagen
    {
        return Imagen::create(['nombre' => $nombre, 'ruta' => "comprobantes/{$nombre}"]);
    }

    public function test_registrar_crea_multiples_comprobantes(): void
    {
        $cuota = $this->cuota();
        $a = $this->imagen('a.jpg');
        $b = $this->imagen('b.jpg');

        $cobro = app(CobroService::class)->registrar($cuota, [
            'monto' => 5000,
            'comprobante_ids' => [$a->id, $b->id],
        ]);

        $this->assertSame(2, $cobro->comprobantes()->count());

        $cobro->load('comprobantes.imagen');
        $this->assertEquals(
            ['comprobantes/a.jpg', 'comprobantes/b.jpg'],
            $cobro->comprobantes->pluck('ruta')->sort()->values()->all()
        );
    }

    public function test_comprobante_id_legacy_se_acepta_como_uno_solo(): void
    {
        $cuota = $this->cuota();
        $a = $this->imagen('legacy.jpg');

        $cobro = app(CobroService::class)->registrar($cuota, [
            'monto' => 5000,
            'comprobante_id' => $a->id,
        ]);

        $this->assertSame(1, $cobro->comprobantes()->count());
        $this->assertSame($a->id, (int) $cobro->comprobantes()->first()->imagen_id);
    }

    public function test_sincronizar_reemplaza_set_y_filtra_nulos_y_duplicados(): void
    {
        $cuota = $this->cuota();
        $a = $this->imagen('x.jpg');
        $b = $this->imagen('y.jpg');
        $service = app(CobroService::class);

        $cobro = $service->registrar($cuota, ['monto' => 5000, 'comprobante_ids' => [$a->id]]);
        $this->assertSame(1, $cobro->comprobantes()->count());

        // Reemplaza por [b, b, null] → queda solo b (dedup + filtra null).
        $service->sincronizarComprobantes($cobro, [$b->id, $b->id, null]);
        $this->assertSame(1, $cobro->comprobantes()->count());
        $this->assertSame($b->id, (int) $cobro->comprobantes()->first()->imagen_id);
    }
}

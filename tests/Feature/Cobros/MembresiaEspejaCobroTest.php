<?php

namespace Tests\Feature\Cobros;

use App\Models\EstadoCuentaMembresia;
use App\Models\Membresia;
use App\Models\MetodoPago;
use App\Models\User;
use App\Services\CobroService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Membresías con el cobro como FUENTE DE VERDAD: marcar pagada crea el cobro,
 * despagar/anular lo da de baja, y la caché de la cuota (pagado/fecha/modo/info)
 * se deriva del cobro. Registrar/borrar un cobro recomputa la cuota.
 */
class MembresiaEspejaCobroTest extends TestCase
{
    use DatabaseTransactions;

    private function cuota(bool $pagado, string $modo = 'Transferencia', float $importe = 8000): EstadoCuentaMembresia
    {
        $user = User::factory()->create();
        $membresia = Membresia::create(['nombre' => 'Mensual Test', 'valor' => $importe]);

        return EstadoCuentaMembresia::create([
            'user_id' => $user->id,
            'membresia_id' => $membresia->id,
            'mes_pagado' => '2026-07',
            'importe' => $importe,
            'modo' => $modo,
            'observaciones' => '',
            'info_pago' => '',
            'pagado' => $pagado,
            'estado' => EstadoCuentaMembresia::ESTADO_ACTIVA,
        ]);
    }

    public function test_marcar_pagada_crea_el_cobro_espejo(): void
    {
        $cuota = $this->cuota(pagado: true);

        app(CobroService::class)->sincronizarMembresia($cuota);

        $cobros = $cuota->cobros()->get();
        $this->assertCount(1, $cobros);
        $this->assertEquals(8000, (float) $cobros->first()->monto);
        $this->assertSame('membresia_cuota', DB::table('cobros')->where('id', $cobros->first()->id)->value('cobrable_type'));
        $this->assertSame('membresia', $cobros->first()->origen);
        $this->assertEquals(MetodoPago::where('nombre', 'Transferencia')->value('id'), $cobros->first()->metodo_pago_id);
    }

    public function test_despagar_da_de_baja_el_cobro(): void
    {
        $cuota = $this->cuota(pagado: true);
        $svc = app(CobroService::class);
        $svc->sincronizarMembresia($cuota);
        $this->assertSame(1, $cuota->cobros()->count());

        $cuota->pagado = false;
        $cuota->save();
        $svc->sincronizarMembresia($cuota);

        $this->assertSame(0, $cuota->cobros()->count());
        $this->assertSame(1, $cuota->cobros()->onlyTrashed()->count());
    }

    public function test_el_cobro_es_fuente_de_verdad_del_pago(): void
    {
        $cuota = $this->cuota(pagado: false);
        $svc = app(CobroService::class);
        $this->assertFalse((bool) $cuota->pagado);

        // Registrar un cobro marca la cuota como pagada (cobro → cuota).
        $svc->registrar($cuota, ['monto' => 8000, 'origen' => 'manual']);
        $cuota->refresh();
        $this->assertTrue((bool) $cuota->pagado);

        // Anular (borrar) el cobro la vuelve a dejar impaga.
        $cuota->cobros()->delete();
        $svc->recalcularMembresia($cuota);
        $cuota->refresh();
        $this->assertFalse((bool) $cuota->pagado);
    }
}

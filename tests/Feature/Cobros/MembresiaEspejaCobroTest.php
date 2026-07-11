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
 * Espejo UNIDIRECCIONAL de membresías: la cuota manda; marcar pagada crea el cobro,
 * despagar lo da de baja. Crear/editar un cobro NO modifica la cuota.
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

    public function test_crear_un_cobro_no_modifica_la_cuota(): void
    {
        $cuota = $this->cuota(pagado: true);
        app(CobroService::class)->sincronizarMembresia($cuota);

        // Alta directa de otro cobro: no debe tocar el estado de la cuota (unidireccional).
        app(CobroService::class)->registrar($cuota, ['monto' => 100, 'origen' => 'manual']);

        $cuota->refresh();
        $this->assertTrue((bool) $cuota->pagado);
        $this->assertSame(EstadoCuentaMembresia::ESTADO_ACTIVA, $cuota->estado);
    }
}

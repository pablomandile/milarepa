<?php

namespace Tests\Feature\Cobros;

use App\Models\Cobro;
use App\Models\EstadoCuentaMembresia;
use App\Models\Imagen;
use App\Models\Membresia;
use App\Models\User;
use App\Services\CobroService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Fase 2: las cuotas de membresía adoptan el modelo "nunca hay comprobante sin
 * cobro". Un pago informado y no aprobado queda como cobro `a_revisar` (no suma
 * plata), y aprobarlo lo convierte en confirmado conservando el comprobante.
 *
 * El punto delicado es `sincronizarMembresia`: su `cobros()->delete()` borraba
 * TODO, así que un cobro a revisar creado en una pasada desaparecía en la
 * siguiente junto con su comprobante.
 */
class CobroMembresiaARevisarTest extends TestCase
{
    use DatabaseTransactions;

    private function cuota(bool $pagado = false, ?int $comprobanteId = null): EstadoCuentaMembresia
    {
        $user = User::factory()->create();
        $membresia = Membresia::create(['nombre' => 'MAR' . substr(uniqid(), -6), 'valor' => 5000]);

        return EstadoCuentaMembresia::create([
            'user_id' => $user->id,
            'membresia_id' => $membresia->id,
            'mes_pagado' => '2026-08',
            'importe' => 5000,
            'observaciones' => '',
            'info_pago' => '',
            'pagado' => $pagado,
            'comprobante_imagen_id' => $comprobanteId,
            'estado' => EstadoCuentaMembresia::ESTADO_ACTIVA,
        ]);
    }

    private function imagen(string $nombre): Imagen
    {
        return Imagen::create(['nombre' => $nombre, 'ruta' => "comprobantes/{$nombre}"]);
    }

    public function test_comprobante_informado_sin_aprobar_crea_cobro_a_revisar(): void
    {
        $imagen = $this->imagen('cuota-' . uniqid() . '.jpg');
        $cuota = $this->cuota(false, $imagen->id);

        app(CobroService::class)->sincronizarMembresia($cuota);

        $cobro = $cuota->cobros()->aRevisar()->first();
        $this->assertNotNull($cobro, 'debería quedar un cobro a revisar');
        $this->assertSame(Cobro::ESTADO_A_REVISAR, $cobro->estado);
        $this->assertSame(1, $cobro->comprobantes()->count());

        // No suma plata ni marca la cuota como pagada.
        $this->assertEquals(0.0, $cuota->montoCobrado());
        $this->assertFalse($cuota->fresh()->pagado);
    }

    public function test_una_segunda_sincronizacion_no_borra_el_cobro_a_revisar(): void
    {
        $imagen = $this->imagen('cuota-dos-' . uniqid() . '.jpg');
        $cuota = $this->cuota(false, $imagen->id);

        $servicio = app(CobroService::class);
        $servicio->sincronizarMembresia($cuota);
        $idPrimero = $cuota->cobros()->aRevisar()->first()->id;

        // Antes, este segundo paso hacía cobros()->delete() y se llevaba puesto
        // el cobro a revisar con su comprobante.
        $servicio->sincronizarMembresia($cuota->fresh());

        $this->assertSame(1, $cuota->cobros()->aRevisar()->count());
        $this->assertSame($idPrimero, $cuota->cobros()->aRevisar()->first()->id);
    }

    public function test_aprobar_la_cuota_confirma_el_cobro_y_conserva_el_comprobante(): void
    {
        $imagen = $this->imagen('cuota-ok-' . uniqid() . '.jpg');
        $cuota = $this->cuota(false, $imagen->id);

        $servicio = app(CobroService::class);
        $servicio->sincronizarMembresia($cuota);
        $this->assertSame(1, $cuota->cobros()->aRevisar()->count());

        // El admin la marca pagada.
        $cuota->update(['pagado' => true, 'fecha_pago' => '2026-08-15', 'modo' => 'Efectivo']);
        $servicio->sincronizarMembresia($cuota);

        $this->assertSame(0, $cuota->cobros()->aRevisar()->count(), 'el pendiente se da de baja');
        $confirmados = $cuota->cobros()->confirmados()->get();
        $this->assertCount(1, $confirmados, 'no se duplica plata');
        $this->assertEquals(5000.0, (float) $confirmados->first()->monto);
        $this->assertSame(1, $confirmados->first()->comprobantes()->count(), 'el comprobante viaja al cobro confirmado');
        $this->assertTrue($cuota->fresh()->pagado);
    }

    public function test_cuota_sin_comprobante_no_genera_cobro(): void
    {
        $cuota = $this->cuota(false);

        app(CobroService::class)->sincronizarMembresia($cuota);

        $this->assertSame(0, $cuota->cobros()->count());
        $this->assertFalse($cuota->fresh()->pagado);
    }

    public function test_desmarcar_pagada_borra_el_confirmado_pero_no_los_a_revisar(): void
    {
        $imagen = $this->imagen('cuota-baja-' . uniqid() . '.jpg');
        $cuota = $this->cuota(true, $imagen->id);
        $cuota->update(['fecha_pago' => '2026-08-15', 'modo' => 'Efectivo']);

        $servicio = app(CobroService::class);
        $servicio->sincronizarMembresia($cuota);
        $this->assertSame(1, $cuota->cobros()->confirmados()->count());

        // El admin la desmarca: el confirmado se va y queda el comprobante informado.
        $cuota->update(['pagado' => false]);
        $servicio->sincronizarMembresia($cuota);

        $this->assertSame(0, $cuota->cobros()->confirmados()->count());
        $this->assertSame(1, $cuota->cobros()->aRevisar()->count());
    }
}

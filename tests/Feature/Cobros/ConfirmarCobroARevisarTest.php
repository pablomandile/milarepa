<?php

namespace Tests\Feature\Cobros;

use App\Models\Actividad;
use App\Models\Cobro;
use App\Models\EsquemaPrecio;
use App\Models\EsquemaPrecioMembresia;
use App\Models\Imagen;
use App\Models\Inscripcion;
use App\Models\Membresia;
use App\Models\MetodoPago;
use App\Models\Modalidad;
use App\Models\TipoActividad;
use App\Models\User;
use App\Services\CobroService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Al marcar el pago (Saldado/Parcial), el cobro `a_revisar` existente se CONFIRMA
 * con los datos reales (monto/fecha/método/registrador) en vez de duplicarse.
 * Si el saldo ya está cubierto (p.ej. pagó por MP), el pendiente se cierra sin
 * duplicar plata: sus comprobantes pasan al confirmado y se soft-deletea.
 */
class ConfirmarCobroARevisarTest extends TestCase
{
    use DatabaseTransactions;

    private function inscripcion(float $monto = 10000): Inscripcion
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);

        $general = Membresia::create(['nombre' => 'Sin membresía', 'valor' => 0]);
        $esquema = EsquemaPrecio::create(['nombre' => 'Esquema Confirmar AR']);
        EsquemaPrecioMembresia::create([
            'esquema_precio_id' => $esquema->id,
            'membresia_id' => $general->id,
            'moneda_id' => DB::table('monedas')->value('id'),
            'precio' => $monto,
        ]);

        $actividad = Actividad::create([
            'nombre' => 'Evento Confirmar AR',
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo CAR'])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Presencial CAR'])->id,
            'esquema_precio_id' => $esquema->id,
            'entidad_id' => DB::table('entidades')->value('id'),
            'fecha_inicio' => '2026-09-01 10:00:00',
            'fecha_fin' => '2026-09-02 20:00:00',
            'estado' => true,
        ]);

        return Inscripcion::create([
            'actividad_id' => $actividad->id,
            'user_id' => User::factory()->create()->id,
            'membresia' => 'Sin membresía',
            'precioGeneral' => $monto,
            'montoActividad' => $monto,
            'montoapagar' => $monto,
            'pago' => 'Pendiente',
            'estado' => 'Registrada',
            'envioLinkStream' => 'No aplica',
            'asistencia' => 'Pendiente',
            'online' => false,
        ]);
    }

    private function imagen(string $nombre): Imagen
    {
        return Imagen::create(['nombre' => $nombre, 'ruta' => "comprobantes/{$nombre}"]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_marcar_saldado_confirma_el_mismo_cobro_sin_duplicar(): void
    {
        $inscripcion = $this->inscripcion(10000);
        $aRevisar = app(CobroService::class)
            ->registrarComprobanteARevisar($inscripcion, $this->imagen('transf.jpg')->id, null, 'checkout');
        $admin = $this->admin();
        $metodoId = MetodoPago::firstOrCreate(['nombre' => 'Transferencia'], ['descripcion' => 'Transferencia', 'tipo_de_pago' => 'Online'])->id;

        $this->actingAs($admin)
            ->patchJson("/estadoinscripciones/{$inscripcion->id}/pago", ['pago' => 'Saldado', 'metodo_pago_id' => $metodoId])
            ->assertOk();

        $inscripcion->refresh();
        $cobros = $inscripcion->cobros()->get();

        // Mismo cobro (id estable), ahora confirmado y con los datos reales.
        $this->assertCount(1, $cobros);
        $cobro = $cobros->first();
        $this->assertSame($aRevisar->id, $cobro->id);
        $this->assertSame(Cobro::ESTADO_CONFIRMADO, $cobro->estado);
        $this->assertEquals(10000, (float) $cobro->monto);
        $this->assertSame(now()->toDateString(), $cobro->fecha_pago?->toDateString());
        $this->assertEquals($metodoId, $cobro->metodo_pago_id);
        $this->assertEquals($admin->id, $cobro->registrado_por);
        $this->assertSame('checkout', $cobro->origen); // la procedencia se conserva
        $this->assertSame(1, $cobro->comprobantes()->count());

        $this->assertSame('Saldado', $inscripcion->pago);
        $this->assertEquals(0.0, $inscripcion->saldoPendiente());
    }

    public function test_marcar_parcial_confirma_con_el_monto_indicado(): void
    {
        $inscripcion = $this->inscripcion(10000);
        $aRevisar = app(CobroService::class)
            ->registrarComprobanteARevisar($inscripcion, $this->imagen('parcial.jpg')->id, null, 'checkout');

        $this->actingAs($this->admin())
            ->patchJson("/estadoinscripciones/{$inscripcion->id}/pago", ['pago' => 'Parcial', 'monto_cobrado' => 4000])
            ->assertOk();

        $inscripcion->refresh();
        $cobro = $inscripcion->cobros()->sole();

        $this->assertSame($aRevisar->id, $cobro->id);
        $this->assertSame(Cobro::ESTADO_CONFIRMADO, $cobro->estado);
        $this->assertEquals(4000, (float) $cobro->monto); // el monto provisional se pisa
        $this->assertEquals(4000.0, $inscripcion->montoCobrado());
        $this->assertEquals(6000.0, $inscripcion->saldoPendiente());
        $this->assertSame('Parcial', $inscripcion->pago);
    }

    public function test_saldado_sin_saldo_cierra_el_pendiente_sin_duplicar_plata(): void
    {
        $inscripcion = $this->inscripcion(10000);
        $svc = app(CobroService::class);

        // Comprobante informado primero; después entra el pago aprobado de MP.
        $aRevisar = $svc->registrarComprobanteARevisar($inscripcion, $this->imagen('previo.jpg')->id, null, 'checkout');
        $mp = $svc->registrar($inscripcion, ['monto' => 10000, 'origen' => 'mercadopago', 'fecha_pago' => now()->toDateString()]);
        $inscripcion->refresh();
        $this->assertSame('Saldado', $inscripcion->pago);

        // El admin re-marca Saldado: el pendiente se cierra, no se crea otro cobro.
        $this->actingAs($this->admin())
            ->patchJson("/estadoinscripciones/{$inscripcion->id}/pago", ['pago' => 'Saldado'])
            ->assertOk();

        $inscripcion->refresh();
        $cobros = $inscripcion->cobros()->get();
        $this->assertCount(1, $cobros);
        $this->assertSame($mp->id, $cobros->first()->id);
        $this->assertEquals(10000.0, $inscripcion->montoCobrado());

        // El comprobante del pendiente se preservó en el confirmado; el pendiente quedó soft-deleted.
        $this->assertSame(1, $mp->comprobantes()->count());
        $this->assertSoftDeleted('cobros', ['id' => $aRevisar->id]);
    }
}

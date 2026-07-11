<?php

namespace Tests\Feature\Cobros;

use App\Models\Actividad;
use App\Models\EsquemaPrecio;
use App\Models\EsquemaPrecioMembresia;
use App\Models\Inscripcion;
use App\Models\Membresia;
use App\Models\MetodoPago;
use App\Models\Modalidad;
use App\Models\TipoActividad;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Cuando el admin marca una inscripción como Saldado/Parcial, se registra el cobro
 * en el ledger (con el medio elegido), sin que el label lo determine el ledger
 * (el admin lo fija a mano; recalcular=false en el servicio).
 */
class CobroActividadAdminTest extends TestCase
{
    use DatabaseTransactions;

    private function inscripcion(float $monto = 10000): Inscripcion
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);

        $general = Membresia::create(['nombre' => 'Sin membresía', 'valor' => 0]);
        $esquema = EsquemaPrecio::create(['nombre' => 'Esquema Cobro Admin']);
        EsquemaPrecioMembresia::create([
            'esquema_precio_id' => $esquema->id,
            'membresia_id' => $general->id,
            'moneda_id' => DB::table('monedas')->value('id'),
            'precio' => $monto,
        ]);

        $actividad = Actividad::create([
            'nombre' => 'Evento Cobro Admin',
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo CA2'])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Presencial CA2'])->id,
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

    public function test_marcar_saldado_registra_cobro_por_el_saldo(): void
    {
        $inscripcion = $this->inscripcion(10000);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $metodoId = MetodoPago::firstOrCreate(['nombre' => 'Efectivo'], ['descripcion' => 'Efectivo', 'tipo_de_pago' => 'Presencial'])->id;

        $this->actingAs($admin)
            ->patchJson("/estadoinscripciones/{$inscripcion->id}/pago", ['pago' => 'Saldado', 'metodo_pago_id' => $metodoId])
            ->assertOk();

        $inscripcion->refresh();
        $this->assertSame('Saldado', $inscripcion->pago);

        $cobros = $inscripcion->cobros()->get();
        $this->assertCount(1, $cobros);
        $this->assertEquals(10000, (float) $cobros->first()->monto);
        $this->assertSame('manual', $cobros->first()->origen);
        $this->assertEquals($metodoId, $cobros->first()->metodo_pago_id);
        $this->assertEquals(0.0, $inscripcion->saldoPendiente());
    }

    public function test_marcar_parcial_con_monto_registra_solo_ese_monto(): void
    {
        $inscripcion = $this->inscripcion(10000);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->patchJson("/estadoinscripciones/{$inscripcion->id}/pago", ['pago' => 'Parcial', 'monto_cobrado' => 4000])
            ->assertOk();

        $inscripcion->refresh();
        $this->assertSame('Parcial', $inscripcion->pago);
        $this->assertEquals(4000, $inscripcion->montoCobrado());
        $this->assertEquals(6000.0, $inscripcion->saldoPendiente());
    }
}

<?php

namespace Tests\Feature\Cobros;

use App\Models\Actividad;
use App\Models\EsquemaPrecio;
use App\Models\EsquemaPrecioMembresia;
use App\Models\EstadoCuentaMembresia;
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
 * Núcleo del ledger de cobros: relación polimórfica, morph map, montos derivados,
 * recálculo del estado de pago y catálogo de métodos de pago. No ejercita todavía
 * el wiring por dominio (eso es de fases posteriores).
 */
class CobroNucleoTest extends TestCase
{
    use DatabaseTransactions;

    private function cuotaMembresia(float $importe = 8000): EstadoCuentaMembresia
    {
        $user = User::factory()->create();
        $membresia = Membresia::create(['nombre' => 'Mensual Test', 'valor' => $importe]);

        return EstadoCuentaMembresia::create([
            'user_id' => $user->id,
            'membresia_id' => $membresia->id,
            'mes_pagado' => '2026-07',
            'importe' => $importe,
            'modo' => 'Efectivo',
            'observaciones' => '',
            'info_pago' => '',
            'pagado' => false,
            'estado' => EstadoCuentaMembresia::ESTADO_ACTIVA,
        ]);
    }

    private function inscripcion(float $montoapagar = 10000): Inscripcion
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);

        $general = Membresia::create(['nombre' => 'Sin membresía', 'valor' => 0]);
        $esquema = EsquemaPrecio::create(['nombre' => 'Esquema Cobro Test']);
        EsquemaPrecioMembresia::create([
            'esquema_precio_id' => $esquema->id,
            'membresia_id' => $general->id,
            'moneda_id' => DB::table('monedas')->value('id'),
            'precio' => $montoapagar,
        ]);

        $actividad = Actividad::create([
            'nombre' => 'Evento Cobro Test',
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo CT'])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Presencial CT'])->id,
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
            'precioGeneral' => $montoapagar,
            'montoActividad' => $montoapagar,
            'montoapagar' => $montoapagar,
            'pago' => 'Pendiente',
            'estado' => 'Registrada',
            'envioLinkStream' => 'No aplica',
            'asistencia' => 'Pendiente',
            'online' => false,
        ]);
    }

    public function test_cobro_polimorfico_usa_alias_de_morph_map(): void
    {
        $cuota = $this->cuotaMembresia();
        $cobro = $cuota->cobros()->create(['monto' => 1000, 'origen' => 'manual']);

        $this->assertSame(1, $cuota->cobros()->count());
        $this->assertSame('membresia_cuota', DB::table('cobros')->where('id', $cobro->id)->value('cobrable_type'));
        $this->assertInstanceOf(EstadoCuentaMembresia::class, $cobro->fresh()->cobrable);
    }

    public function test_monto_cobrado_y_saldo_pendiente(): void
    {
        $cuota = $this->cuotaMembresia(8000);
        $cuota->cobros()->create(['monto' => 3000]);
        $cuota->cobros()->create(['monto' => 2000]);

        $this->assertSame(5000.0, $cuota->montoCobrado());
        $this->assertSame(3000.0, $cuota->saldoPendiente());
    }

    public function test_registrar_recalcula_estado_de_inscripcion(): void
    {
        $inscripcion = $this->inscripcion(10000);
        $service = app(CobroService::class);

        $service->registrar($inscripcion, ['monto' => 4000]);
        $inscripcion->refresh();
        $this->assertSame('Parcial', $inscripcion->pago);
        $this->assertSame(6000.0, $inscripcion->saldoPendiente());

        $service->registrar($inscripcion, ['monto' => 6000]);
        $inscripcion->refresh();
        $this->assertSame('Saldado', $inscripcion->pago);
        $this->assertSame('Confirmada', $inscripcion->estado);
        $this->assertSame(0.0, $inscripcion->saldoPendiente());
    }

    public function test_resolver_metodo_pago_por_nombre(): void
    {
        $service = app(CobroService::class);

        // El resolver mapea por nombre normalizado (case/acento-insensible). Los métodos se
        // crean acá para no depender de que el seed de la migración esté en la base de tests.
        $suscripcion = MetodoPago::firstOrCreate(['nombre' => 'Suscripción'], ['descripcion' => 'Suscripción', 'tipo_de_pago' => 'Online']);
        $otro = MetodoPago::firstOrCreate(['nombre' => 'Otro'], ['descripcion' => 'Otro', 'tipo_de_pago' => 'Presencial']);

        $this->assertEquals($suscripcion->id, $service->resolverMetodoPago('Suscripción'));
        $this->assertEquals($otro->id, $service->resolverMetodoPago('otro')); // case/acento-insensible
        $this->assertNull($service->resolverMetodoPago(''));
        $this->assertNull($service->resolverMetodoPago('Inexistente XYZ'));
    }
}

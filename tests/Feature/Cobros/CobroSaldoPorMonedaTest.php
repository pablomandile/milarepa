<?php

namespace Tests\Feature\Cobros;

use App\Models\Actividad;
use App\Models\Cobro;
use App\Models\EsquemaPrecio;
use App\Models\Inscripcion;
use App\Models\MetodoPago;
use App\Models\Modalidad;
use App\Models\Moneda;
use App\Models\TipoActividad;
use App\Models\User;
use App\Services\CobroService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Una inscripción con el total dividido (BUSINESS_RULES §2.1bis) debe dos cosas
 * distintas —la porción en su moneda y la porción en pesos— y cada una se salda
 * por separado, con su propio cobro. Mismo criterio que ya usaba el POS: sumarlas
 * en un único cobro daba un importe que no existe en ninguna moneda.
 */
class CobroSaldoPorMonedaTest extends TestCase
{
    use DatabaseTransactions;

    private Moneda $principal;
    private Moneda $secundaria;

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);

        $this->principal = Moneda::principal()
            ?? Moneda::create(['nombre' => 'Pesos Test', 'simbolo' => '$', 'es_principal' => true]);
        $this->secundaria = Moneda::create(['nombre' => 'Dolar SPM ' . substr(uniqid(), -6), 'simbolo' => 'U$T']);
    }

    /** Inscripción con `$enMoneda` en la moneda dada + `$enPesos` en la principal. */
    private function inscripcion(float $enMoneda = 120, float $enPesos = 2000, ?int $monedaId = null): Inscripcion
    {
        $actividad = Actividad::create([
            'nombre' => 'Evento Saldo Moneda ' . uniqid(),
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo SPM ' . uniqid()])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Presencial SPM ' . uniqid()])->id,
            'esquema_precio_id' => EsquemaPrecio::create(['nombre' => 'Esquema SPM ' . uniqid()])->id,
            'entidad_id' => DB::table('entidades')->value('id'),
            'fecha_inicio' => '2026-12-01 10:00:00',
            'fecha_fin' => '2026-12-02 20:00:00',
            'estado' => true,
        ]);

        return Inscripcion::create([
            'actividad_id' => $actividad->id,
            'user_id' => User::factory()->create()->id,
            'membresia' => 'Sin membresía',
            'precioGeneral' => $enMoneda,
            'montoActividad' => $enMoneda,
            'montoapagar' => $enMoneda,
            'moneda_id' => $monedaId ?? $this->secundaria->id,
            'monto_moneda_principal' => $enPesos,
            'pago' => 'Pendiente',
            'estado' => 'Registrada',
            'envioLinkStream' => 'No aplica',
            'asistencia' => 'Pendiente',
            'online' => false,
        ]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function imagen(): int
    {
        return DB::table('imagenes')->insertGetId([
            'nombre' => 'comprobante.jpg',
            'ruta' => 'comprobantes/spm-' . uniqid() . '.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_marcar_saldado_crea_un_cobro_por_moneda(): void
    {
        $inscripcion = $this->inscripcion(120, 2000);
        $metodoId = MetodoPago::firstOrCreate(['nombre' => 'Efectivo'], ['descripcion' => 'Efectivo', 'tipo_de_pago' => 'Presencial'])->id;

        $this->actingAs($this->admin())
            ->patchJson("/estadoinscripciones/{$inscripcion->id}/pago", ['pago' => 'Saldado', 'metodo_pago_id' => $metodoId])
            ->assertOk();

        $cobros = $inscripcion->cobros()->get()->keyBy('moneda_id');

        $this->assertCount(2, $cobros, 'Cada moneda tiene que tener su propio cobro.');
        $this->assertEquals(120, (float) $cobros[$this->secundaria->id]->monto);
        $this->assertEquals(2000, (float) $cobros[$this->principal->id]->monto);

        $inscripcion->refresh();
        $this->assertSame('Saldado', $inscripcion->pago);
        $this->assertSame([], app(CobroService::class)->saldoPendientePorMoneda($inscripcion));
    }

    public function test_cobrar_solo_una_porcion_deja_la_otra_pendiente(): void
    {
        $inscripcion = $this->inscripcion(120, 2000);

        app(CobroService::class)->registrar($inscripcion, [
            'monto' => 120,
            'moneda_id' => $this->secundaria->id,
            'fecha_pago' => '2026-08-15',
        ]);

        $inscripcion->refresh();
        $this->assertSame('Parcial', $inscripcion->pago);
        $this->assertEquals(
            [$this->principal->id => 2000.0],
            app(CobroService::class)->saldoPendientePorMoneda($inscripcion)
        );
    }

    public function test_el_comprobante_a_revisar_toma_el_saldo_de_su_moneda(): void
    {
        $inscripcion = $this->inscripcion(120, 2000);

        $cobro = app(CobroService::class)->registrarComprobanteARevisar($inscripcion, $this->imagen());

        $this->assertSame(Cobro::ESTADO_A_REVISAR, $cobro->estado);
        $this->assertSame($this->secundaria->id, $cobro->moneda_id);
        // 120, no 2120: el saldo mezclado de las dos monedas no es un importe real.
        $this->assertEquals(120, (float) $cobro->monto);
    }

    public function test_confirmar_una_moneda_no_toca_el_pendiente_de_la_otra(): void
    {
        $inscripcion = $this->inscripcion(120, 2000);
        $cobros = app(CobroService::class);

        $enMoneda = $cobros->registrarComprobanteARevisar($inscripcion, $this->imagen());
        $enPesos = $cobros->registrarComprobanteARevisar(
            $inscripcion,
            $this->imagen(),
            monedaId: $this->principal->id
        );

        $this->assertNotSame($enMoneda->id, $enPesos->id, 'Hay un cobro a revisar por moneda.');
        $this->assertEquals(2000, (float) $enPesos->monto);

        // El admin verifica sólo la transferencia en pesos.
        $cobros->confirmarORegistrar($inscripcion, [
            'monto' => 2000,
            'moneda_id' => $this->principal->id,
            'fecha_pago' => '2026-08-15',
        ]);

        $this->assertSame(Cobro::ESTADO_CONFIRMADO, $enPesos->fresh()->estado);
        $this->assertSame(Cobro::ESTADO_A_REVISAR, $enMoneda->fresh()->estado);
    }

    public function test_una_inscripcion_en_pesos_sigue_creando_un_solo_cobro(): void
    {
        $inscripcion = $this->inscripcion(10000, 0, $this->principal->id);

        $this->actingAs($this->admin())
            ->patchJson("/estadoinscripciones/{$inscripcion->id}/pago", ['pago' => 'Saldado'])
            ->assertOk();

        $cobros = $inscripcion->cobros()->get();
        $this->assertCount(1, $cobros);
        $this->assertEquals(10000, (float) $cobros->first()->monto);
        $this->assertSame('Saldado', $inscripcion->refresh()->pago);
    }

    public function test_una_inscripcion_gratuita_queda_saldada(): void
    {
        $inscripcion = $this->inscripcion(0, 0, $this->principal->id);

        app(CobroService::class)->recalcularEstadoPago($inscripcion);

        $this->assertSame('Saldado', $inscripcion->refresh()->pago);
        $this->assertSame([], app(CobroService::class)->saldoPendientePorMoneda($inscripcion));
    }
}

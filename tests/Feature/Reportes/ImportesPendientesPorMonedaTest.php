<?php

namespace Tests\Feature\Reportes;

use App\Models\Actividad;
use App\Models\EsquemaPrecio;
use App\Models\Inscripcion;
use App\Models\Modalidad;
use App\Models\Moneda;
use App\Models\TipoActividad;
use App\Models\User;
use App\Services\ReporteInscripcionesPorActividadService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * El importe pendiente por actividad va abierto por moneda. Antes era un
 * SUM(montoapagar) que metía pesos y dólares en el mismo número y además se
 * olvidaba de monto_moneda_principal (la porción del total dividido).
 */
class ImportesPendientesPorMonedaTest extends TestCase
{
    use DatabaseTransactions;

    private Moneda $principal;
    private Moneda $secundaria;

    private function monedas(): void
    {
        $this->principal = Moneda::principal()
            ?? Moneda::create(['nombre' => 'Pesos Test', 'simbolo' => '$', 'es_principal' => true]);
        $this->secundaria = Moneda::create(['nombre' => 'Dolar Test ' . uniqid(), 'simbolo' => 'U$T']);
    }

    private function actividad(): Actividad
    {
        return Actividad::create([
            'nombre' => 'Evento Reporte ' . uniqid(),
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo Rep ' . uniqid()])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Presencial Rep ' . uniqid()])->id,
            'esquema_precio_id' => EsquemaPrecio::create(['nombre' => 'Esquema Rep ' . uniqid()])->id,
            'entidad_id' => DB::table('entidades')->value('id'),
            'fecha_inicio' => '2026-12-10 10:00:00',
            'fecha_fin' => '2026-12-11 20:00:00',
            'estado' => true,
        ]);
    }

    private function inscripcion(Actividad $actividad, ?int $monedaId, float $monto, ?float $porcionPrincipal = null): Inscripcion
    {
        $user = User::create([
            'name' => 'Reporte Moneda',
            'email' => 'reporte.moneda' . uniqid() . '@example.com',
            'password' => Hash::make('x'),
        ]);

        return Inscripcion::create([
            'actividad_id' => $actividad->id,
            'user_id' => $user->id,
            'membresia' => 'Sin membresía',
            'precioGeneral' => $monto,
            'montoActividad' => $monto,
            'montoapagar' => $monto,
            'moneda_id' => $monedaId,
            'monto_moneda_principal' => $porcionPrincipal,
            'pago' => 'Pendiente',
            'estado' => 'Registrada',
            'envioLinkStream' => 'No aplica',
            'asistencia' => 'Pendiente',
            'online' => false,
        ]);
    }

    private function importes(Actividad $actividad): array
    {
        return app(ReporteInscripcionesPorActividadService::class)
            ->importesPendientesPorMoneda([$actividad->id])[$actividad->id] ?? [];
    }

    public function test_separa_los_importes_por_moneda(): void
    {
        $this->monedas();
        $actividad = $this->actividad();

        $this->inscripcion($actividad, $this->principal->id, 50000);
        $this->inscripcion($actividad, null, 30000);                 // legacy = principal
        $this->inscripcion($actividad, $this->secundaria->id, 120);

        $importes = $this->importes($actividad);

        $this->assertSame(80000.0, $importes['principal']);
        $this->assertCount(2, $importes['por_moneda']);

        // La principal siempre va primero.
        $this->assertSame($this->principal->id, $importes['por_moneda'][0]['moneda_id']);
        $this->assertSame(80000.0, $importes['por_moneda'][0]['importe']);

        $this->assertSame($this->secundaria->id, $importes['por_moneda'][1]['moneda_id']);
        $this->assertSame(120.0, $importes['por_moneda'][1]['importe']);
        $this->assertSame('U$T', $importes['por_moneda'][1]['simbolo']);
    }

    public function test_la_porcion_del_total_dividido_suma_a_la_principal(): void
    {
        $this->monedas();
        $actividad = $this->actividad();

        // Inscripción en dólares con servicios sin precio en dólares: 120 USD + 2000 pesos.
        $this->inscripcion($actividad, $this->secundaria->id, 120, 2000);

        $importes = $this->importes($actividad);

        $this->assertSame(2000.0, $importes['principal']);
        $this->assertCount(2, $importes['por_moneda']);
        $this->assertSame(2000.0, $importes['por_moneda'][0]['importe']);
        $this->assertSame(120.0, $importes['por_moneda'][1]['importe']);
    }

    public function test_actividad_solo_en_pesos_se_comporta_como_antes(): void
    {
        $this->monedas();
        $actividad = $this->actividad();

        $this->inscripcion($actividad, null, 15000);
        $this->inscripcion($actividad, null, 5000);

        $importes = $this->importes($actividad);

        $this->assertSame(20000.0, $importes['principal']);
        $this->assertCount(1, $importes['por_moneda']);
    }

    public function test_las_inscripciones_saldadas_no_cuentan(): void
    {
        $this->monedas();
        $actividad = $this->actividad();

        $saldada = $this->inscripcion($actividad, $this->secundaria->id, 999);
        $saldada->update(['pago' => 'Saldado']);
        $this->inscripcion($actividad, null, 7000);

        $importes = $this->importes($actividad);

        $this->assertSame(7000.0, $importes['principal']);
        $this->assertCount(1, $importes['por_moneda']);
    }

    public function test_la_pantalla_por_actividad_expone_el_desglose(): void
    {
        $this->monedas();
        $actividad = $this->actividad();
        $this->inscripcion($actividad, $this->secundaria->id, 120, 2000);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('inscripciones.por-actividad'))
            ->assertOk()
            ->assertSee('pendiente_importes', escape: false)
            ->assertSee('U$T', escape: false);
    }
}

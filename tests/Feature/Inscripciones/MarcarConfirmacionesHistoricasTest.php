<?php

namespace Tests\Feature\Inscripciones;

use App\Models\Actividad;
use App\Models\EsquemaPrecio;
use App\Models\Inscripcion;
use App\Models\Modalidad;
use App\Models\TipoActividad;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * El comando corta por fecha de inicio de la actividad: lo anterior al corte queda
 * como confirmación ya enviada (para no mandar mails retroactivos sobre datos
 * históricos) y lo posterior sigue pendiente, que es lo que sí debe salir.
 *
 * Las aserciones son sobre las inscripciones que crea el test, nunca sobre
 * conteos globales: la base de test tiene datos reales.
 */
class MarcarConfirmacionesHistoricasTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        foreach (glob(storage_path('app/confirmaciones_marcadas_*.json')) ?: [] as $archivo) {
            @unlink($archivo);
        }

        parent::tearDown();
    }

    private function inscripcion(string $fechaInicio, string $pago = 'Saldado'): Inscripcion
    {
        $actividad = Actividad::create([
            'nombre' => 'Evento MCH ' . uniqid(),
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo MCH ' . uniqid()])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Presencial MCH ' . uniqid()])->id,
            'esquema_precio_id' => EsquemaPrecio::create(['nombre' => 'Esquema MCH ' . uniqid()])->id,
            'entidad_id' => DB::table('entidades')->value('id'),
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaInicio,
            'estado' => true,
        ]);

        return Inscripcion::create([
            'actividad_id' => $actividad->id,
            'user_id' => User::factory()->create()->id,
            'membresia' => 'Sin membresía',
            'precioGeneral' => 0,
            'montoActividad' => 0,
            'montoapagar' => 0,
            'pago' => $pago,
            'estado' => 'Registrada',
            'envioConfirmacion' => 'Pendiente',
            'envioGrabacion' => 'No aplica',
            'asistencia' => 'Pendiente',
            'online' => false,
        ]);
    }

    public function test_marca_las_anteriores_al_corte_y_respeta_las_posteriores(): void
    {
        $vieja = $this->inscripcion('2026-08-30 10:00:00');
        $nueva = $this->inscripcion('2026-09-05 10:00:00');

        $this->artisan('inscripciones:marcar-confirmaciones-enviadas', ['--hasta' => '2026-09-01'])
            ->assertSuccessful();

        $this->assertSame('Enviada', $vieja->refresh()->envioConfirmacion);
        $this->assertSame('Pendiente', $nueva->refresh()->envioConfirmacion);
    }

    public function test_alcanza_tambien_a_las_no_saldadas(): void
    {
        // Una inscripción vieja sin saldar no se enviaría hoy, pero se marca igual:
        // si mañana se concilia y pasa a Saldado, mandaría el mail retroactivo.
        $pendiente = $this->inscripcion('2026-07-01 10:00:00', 'Pendiente');

        $this->artisan('inscripciones:marcar-confirmaciones-enviadas', ['--hasta' => '2026-09-01'])
            ->assertSuccessful();

        $this->assertSame('Enviada', $pendiente->refresh()->envioConfirmacion);
    }

    public function test_el_dry_run_no_escribe(): void
    {
        $vieja = $this->inscripcion('2026-08-30 10:00:00');

        $this->artisan('inscripciones:marcar-confirmaciones-enviadas', ['--hasta' => '2026-09-01', '--dry-run' => true])
            ->assertSuccessful();

        $this->assertSame('Pendiente', $vieja->refresh()->envioConfirmacion);
    }

    public function test_sin_fecha_de_corte_falla(): void
    {
        $vieja = $this->inscripcion('2026-08-30 10:00:00');

        $this->artisan('inscripciones:marcar-confirmaciones-enviadas')->assertFailed();

        $this->assertSame('Pendiente', $vieja->refresh()->envioConfirmacion);
    }

    public function test_no_toca_updated_at(): void
    {
        $vieja = $this->inscripcion('2026-08-30 10:00:00');
        DB::table('inscripciones')->where('id', $vieja->id)->update(['updated_at' => '2026-01-15 10:00:00']);

        $this->artisan('inscripciones:marcar-confirmaciones-enviadas', ['--hasta' => '2026-09-01'])
            ->assertSuccessful();

        $this->assertSame(
            '2026-01-15 10:00:00',
            (string) DB::table('inscripciones')->where('id', $vieja->id)->value('updated_at')
        );
    }
}

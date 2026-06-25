<?php

namespace Tests\Feature\EstadoInscripciones;

use App\Models\Actividad;
use App\Models\Comida;
use App\Models\Grabacion;
use App\Models\Inscripcion;
use App\Models\Modalidad;
use App\Models\TipoActividad;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\EsquemaPrecio;
use Tests\TestCase;

/**
 * Edición admin de inscripciones (EstadoInscripcionesController@update).
 * El monto NO se escribe a mano: se recalcula a partir de los servicios del
 * titular y de los invitados. Al quedar saldada (o sin saldo) pasa a 'Confirmada'.
 */
class EstadoInscripcionesUpdateTest extends TestCase
{
    use DatabaseTransactions;

    private function asegurarPaisDefault(): void
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);
    }

    /** @return array{0: Actividad, 1: Grabacion, 2: Comida} */
    private function crearActividadConServicios(): array
    {
        $this->asegurarPaisDefault();

        $grabacion = Grabacion::create(['nombre' => 'Grabación Edit', 'valor' => 2000]);
        $comida = Comida::create(['nombre' => 'Almuerzo Edit', 'descripcion' => 'Almuerzo', 'precio' => 1500, 'vegano' => false, 'celiaco' => false]);

        $actividad = Actividad::create([
            'nombre' => 'Evento Edit Test',
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo Edit'])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Presencial Edit'])->id,
            'esquema_precio_id' => EsquemaPrecio::create(['nombre' => 'Esquema Edit'])->id,
            'grabacion_id' => $grabacion->id,
            'entidad_id' => DB::table('entidades')->value('id'),
            'fecha_inicio' => '2026-07-01 10:00:00',
            'fecha_fin' => '2026-07-02 20:00:00',
            'estado' => true,
        ]);
        $actividad->comidas()->attach($comida->id);

        return [$actividad, $grabacion, $comida];
    }

    private function crearInscripcion(Actividad $actividad, float $montoActividad, string $pago, string $estado): Inscripcion
    {
        $user = User::create(['name' => 'Insc Edit', 'email' => 'insc.edit' . uniqid() . '@example.com', 'password' => Hash::make('x')]);

        return Inscripcion::create([
            'actividad_id' => $actividad->id,
            'user_id' => $user->id,
            'membresia' => 'Sin membresía',
            'precioGeneral' => $montoActividad,
            'montoActividad' => $montoActividad,
            'montoapagar' => $montoActividad,
            'pago' => $pago,
            'estado' => $estado,
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

    public function test_marcar_saldado_confirma_la_inscripcion(): void
    {
        [$actividad] = $this->crearActividadConServicios();
        $inscripcion = $this->crearInscripcion($actividad, 30000, 'Pendiente', 'Registrada');

        $resp = $this->actingAs($this->admin())->putJson(route('estadoinscripciones.update', $inscripcion->id), [
            'pago' => 'Saldado',
        ]);

        $resp->assertOk()->assertJson(['ok' => true, 'estado' => 'Confirmada']);
        $this->assertDatabaseHas('inscripciones', [
            'id' => $inscripcion->id,
            'pago' => 'Saldado',
            'estado' => 'Confirmada',
            'montoapagar' => 30000,
        ]);
    }

    public function test_agregar_servicios_recalcula_el_monto(): void
    {
        [$actividad, , $comida] = $this->crearActividadConServicios();
        $inscripcion = $this->crearInscripcion($actividad, 10000, 'Pendiente', 'Registrada');

        $resp = $this->actingAs($this->admin())->putJson(route('estadoinscripciones.update', $inscripcion->id), [
            'pago' => 'Pendiente',
            'incluye_grabacion' => true,
            'comidas_ids' => [$comida->id],
        ]);

        // 10000 actividad + 2000 grabación + 1500 comida = 13500
        $resp->assertOk()->assertJson(['estado' => 'Registrada']);
        $inscripcion->refresh();
        $this->assertEquals(13500, (float) $inscripcion->montoapagar);
        $this->assertEquals(2000, (float) $inscripcion->montoGrabacion);
        $this->assertEquals(1500, (float) $inscripcion->montoComidas);
        $this->assertEqualsCanonicalizing([$comida->id], $inscripcion->comidas()->pluck('comidas.id')->all());
    }

    public function test_quitar_servicios_recalcula_a_la_baja(): void
    {
        [$actividad, , $comida] = $this->crearActividadConServicios();
        $inscripcion = $this->crearInscripcion($actividad, 10000, 'Pendiente', 'Registrada');
        // Estado inicial con servicios.
        $inscripcion->update(['montoGrabacion' => 2000, 'montoComidas' => 1500, 'montoapagar' => 13500]);
        $inscripcion->comidas()->sync([$comida->id]);

        $resp = $this->actingAs($this->admin())->putJson(route('estadoinscripciones.update', $inscripcion->id), [
            'pago' => 'Pendiente',
            'incluye_grabacion' => false,
            'comidas_ids' => [],
        ]);

        $resp->assertOk();
        $inscripcion->refresh();
        $this->assertEquals(10000, (float) $inscripcion->montoapagar);
        $this->assertNull($inscripcion->montoGrabacion);
        $this->assertNull($inscripcion->montoComidas);
        $this->assertCount(0, $inscripcion->comidas);
    }

    public function test_agregar_invitado_recalcula_monto_invitados_y_total(): void
    {
        [$actividad, , $comida] = $this->crearActividadConServicios();
        $inscripcion = $this->crearInscripcion($actividad, 10000, 'Pendiente', 'Registrada');

        $resp = $this->actingAs($this->admin())->putJson(route('estadoinscripciones.update', $inscripcion->id), [
            'pago' => 'Pendiente',
            'invitados' => [
                ['nombre' => 'Inv', 'apellido' => 'Uno', 'comidas_ids' => [$comida->id]],
            ],
        ]);

        // invitado = 10000 (precio general) + 1500 comida = 11500 ; total = 10000 + 11500
        $resp->assertOk();
        $inscripcion->refresh();
        $this->assertEquals(11500, (float) $inscripcion->monto_invitados);
        $this->assertEquals(21500, (float) $inscripcion->montoapagar);
        $this->assertCount(1, $inscripcion->invitados);
        $this->assertEquals(11500, (float) $inscripcion->invitados->first()->montoapagar);
    }

    public function test_usuario_sin_rol_no_puede_editar(): void
    {
        [$actividad] = $this->crearActividadConServicios();
        $inscripcion = $this->crearInscripcion($actividad, 10000, 'Pendiente', 'Registrada');
        $noAdmin = User::factory()->create();

        $resp = $this->actingAs($noAdmin)->putJson(route('estadoinscripciones.update', $inscripcion->id), [
            'pago' => 'Saldado',
        ]);

        $resp->assertStatus(403);
    }
}

<?php

namespace Tests\Feature\EstadoInscripciones;

use App\Models\Actividad;
use App\Models\Entidad;
use App\Models\EsquemaPrecio;
use App\Models\Inscripcion;
use App\Models\Modalidad;
use App\Models\TipoActividad;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Actualización de inscripciones (EstadoInscripcionesController@update).
 * Cubre la regla: al quedar saldada (o sin saldo) la inscripción pasa a 'Confirmada'.
 */
class EstadoInscripcionesUpdateTest extends TestCase
{
    use DatabaseTransactions;

    private function asegurarPaisDefault(): void
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);
    }

    private function crearInscripcion(float $monto, string $pago, string $estado): Inscripcion
    {
        $this->asegurarPaisDefault();

        $entidad = Entidad::create(['nombre' => 'Entidad Update Test', 'abreviacion' => 'EUT', 'entidad_principal' => false]);
        $actividad = Actividad::create([
            'nombre' => 'Evento Update Test',
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo Update'])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Modalidad Update'])->id,
            'esquema_precio_id' => EsquemaPrecio::create(['nombre' => 'Esquema Update'])->id,
            'entidad_id' => $entidad->id,
            'fecha_inicio' => '2026-06-13 10:00:00',
            'fecha_fin' => '2026-06-14 20:00:00',
            'estado' => true,
        ]);
        $user = User::create(['name' => 'Insc Update', 'email' => 'insc.update@example.com', 'password' => Hash::make('x')]);

        return Inscripcion::create([
            'actividad_id' => $actividad->id,
            'user_id' => $user->id,
            'membresia' => 'Sin membresía',
            'precioGeneral' => $monto,
            'montoapagar' => $monto,
            'pago' => $pago,
            'estado' => $estado,
            'envioLinkStream' => 'No aplica',
            'asistencia' => 'Pendiente',
            'online' => false,
        ]);
    }

    public function test_marcar_saldado_confirma_la_inscripcion(): void
    {
        $inscripcion = $this->crearInscripcion(30000, 'Pendiente', 'Registrada');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $resp = $this->actingAs($admin)->putJson(route('estadoinscripciones.update', $inscripcion->id), [
            'montoapagar' => 0,
            'pago' => 'Saldado',
        ]);

        $resp->assertOk()->assertJson(['ok' => true, 'estado' => 'Confirmada']);
        $this->assertDatabaseHas('inscripciones', [
            'id' => $inscripcion->id,
            'pago' => 'Saldado',
            'estado' => 'Confirmada',
        ]);
    }

    public function test_editar_con_saldo_pendiente_no_confirma(): void
    {
        $inscripcion = $this->crearInscripcion(0, 'Saldado', 'Registrada');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $resp = $this->actingAs($admin)->putJson(route('estadoinscripciones.update', $inscripcion->id), [
            'montoapagar' => 5000,
            'pago' => 'Pendiente',
        ]);

        $resp->assertOk()->assertJson(['estado' => 'Registrada']);
        $this->assertDatabaseHas('inscripciones', [
            'id' => $inscripcion->id,
            'pago' => 'Pendiente',
            'estado' => 'Registrada',
        ]);
    }
}

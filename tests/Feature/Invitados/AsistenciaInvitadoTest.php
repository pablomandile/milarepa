<?php

namespace Tests\Feature\Invitados;

use App\Models\Actividad;
use App\Models\Inscripcion;
use App\Models\EsquemaPrecio;
use App\Models\Invitado;
use App\Models\Modalidad;
use App\Models\TipoActividad;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Asistencia individual por invitado desde la gestión admin.
 */
class AsistenciaInvitadoTest extends TestCase
{
    use DatabaseTransactions;

    private function asegurarPaisDefault(): void
    {
        DB::statement(
            'INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())',
            ['Argentina'],
        );
    }

    private function crearInvitado(): Invitado
    {
        $this->asegurarPaisDefault();

        $actividad = Actividad::create([
            'nombre' => 'Evento Asistencia Test',
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo Asist'])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Presencial Asist'])->id,
            'esquema_precio_id' => EsquemaPrecio::create(['nombre' => 'Esquema Asist'])->id,
            'entidad_id' => DB::table('entidades')->value('id'),
            'fecha_inicio' => '2026-07-01 10:00:00',
            'fecha_fin' => '2026-07-02 20:00:00',
            'estado' => true,
        ]);

        $owner = User::create(['name' => 'Owner', 'email' => 'owner.asist@example.com', 'password' => Hash::make('x')]);

        $inscripcion = Inscripcion::create([
            'actividad_id' => $actividad->id,
            'user_id' => $owner->id,
            'membresia' => 'Sin membresía',
            'precioGeneral' => 0,
            'montoapagar' => 0,
            'pago' => 'Saldado',
            'estado' => 'Confirmada',
            'envioLinkStream' => 'No aplica',
            'asistencia' => 'Pendiente',
            'online' => false,
        ]);

        return $inscripcion->invitados()->create([
            'nombre' => 'Invitado',
            'apellido' => 'Asist',
            'asistencia' => 'Pendiente',
            'montoActividad' => 0,
            'montoapagar' => 0,
        ]);
    }

    private function admin(): User
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'admin.asist@example.com', 'password' => Hash::make('x')]);
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_admin_marca_asistencia_de_un_invitado(): void
    {
        $invitado = $this->crearInvitado();

        $resp = $this->actingAs($this->admin())
            ->patchJson("/invitados/{$invitado->id}/asistencia", ['asistencia' => 'Presente']);

        $resp->assertOk()->assertJson(['ok' => true, 'asistencia' => 'Presente']);
        $this->assertSame('Presente', $invitado->fresh()->asistencia);
    }

    public function test_usuario_sin_rol_no_puede_marcar_asistencia(): void
    {
        $invitado = $this->crearInvitado();
        $asistente = User::create(['name' => 'Asis', 'email' => 'asis.noadmin@example.com', 'password' => Hash::make('x')]);

        $resp = $this->actingAs($asistente)
            ->patchJson("/invitados/{$invitado->id}/asistencia", ['asistencia' => 'Presente']);

        $resp->assertStatus(403);
        $this->assertSame('Pendiente', $invitado->fresh()->asistencia);
    }

    public function test_rechaza_valor_de_asistencia_invalido(): void
    {
        $invitado = $this->crearInvitado();

        $resp = $this->actingAs($this->admin())
            ->patchJson("/invitados/{$invitado->id}/asistencia", ['asistencia' => 'Quizas']);

        $resp->assertStatus(422);
    }
}

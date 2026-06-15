<?php

namespace Tests\Feature\Usuarios;

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
 * Borrado de usuarios (UsuariosController@destroy).
 * Regla: un usuario con inscripciones NO se borra (FK restrictiva) y se avisa;
 * sin inscripciones, se borra normalmente.
 */
class UsuariosDestroyTest extends TestCase
{
    use DatabaseTransactions;

    private function asegurarPaisDefault(): void
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);
    }

    private function admin(): User
    {
        $this->asegurarPaisDefault();
        $admin = User::factory()->create();
        $admin->assignRole('admin'); // el rol admin tiene el permiso 'delete usuarios'
        return $admin;
    }

    public function test_no_borra_usuario_con_inscripciones_y_avisa(): void
    {
        $this->asegurarPaisDefault();

        $entidad = Entidad::create(['nombre' => 'Entidad Del User', 'abreviacion' => 'EDU', 'entidad_principal' => false]);
        $actividad = Actividad::create([
            'nombre' => 'Evento Del User',
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo DU'])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Modalidad DU'])->id,
            'esquema_precio_id' => EsquemaPrecio::create(['nombre' => 'Esquema DU'])->id,
            'entidad_id' => $entidad->id,
            'fecha_inicio' => '2026-06-13 10:00:00',
            'fecha_fin' => '2026-06-14 20:00:00',
            'estado' => true,
        ]);

        $persona = User::create(['name' => 'Con Insc', 'email' => 'con.insc@example.com', 'password' => Hash::make('x')]);
        Inscripcion::create([
            'actividad_id' => $actividad->id,
            'user_id' => $persona->id,
            'membresia' => 'Sin membresía',
            'precioGeneral' => 0,
            'montoapagar' => 0,
            'pago' => 'Saldado',
            'estado' => 'Confirmada',
            'envioLinkStream' => 'No aplica',
            'asistencia' => 'Pendiente',
            'online' => false,
        ]);

        $resp = $this->actingAs($this->admin())
            ->delete(route('usuarios.destroy', $persona->id));

        $resp->assertRedirect(route('usuarios.index'));
        $resp->assertSessionHas('error');
        // El usuario sigue existiendo (no se borró).
        $this->assertDatabaseHas('users', ['id' => $persona->id]);
    }

    public function test_borra_usuario_sin_inscripciones(): void
    {
        $admin = $this->admin();
        $persona = User::create(['name' => 'Sin Insc', 'email' => 'sin.insc@example.com', 'password' => Hash::make('x')]);

        $resp = $this->actingAs($admin)
            ->delete(route('usuarios.destroy', $persona->id));

        $resp->assertRedirect(route('usuarios.index'));
        $resp->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $persona->id]);
    }
}

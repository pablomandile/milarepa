<?php

namespace Tests\Feature\Usuarios;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * "Ver como usuario": el admin ve la interfaz del rol asistant (solo visual) y
 * vuelve con "Ver como Admin".
 */
class VerComoUsuarioTest extends TestCase
{
    use DatabaseTransactions;

    private function usuarioConRol(string $rol): User
    {
        // Con teléfono: sin él, el dashboard manda a completar el perfil.
        $user = User::factory()->create(['telefono' => '1100000000']);
        $user->assignRole($rol);

        return $user;
    }

    private function permisosAsistant(): array
    {
        return Role::where('name', 'asistant')->firstOrFail()
            ->permissions->pluck('name')->sort()->values()->all();
    }

    public function test_admin_ve_la_interfaz_de_usuario_y_vuelve(): void
    {
        $admin = $this->usuarioConRol('admin');

        // Inicia la simulación: cae en el panel del usuario.
        $this->actingAs($admin)
            ->post(route('ver-como-usuario.iniciar'))
            ->assertRedirect(route('asistant.panel'));

        // Los props que decide el menú son los del asistant.
        $this->actingAs($admin)
            ->get(route('asistant.panel'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Asistant/Panel')
                ->where('user.roles', ['asistant'])
                ->where('user.permissions', fn ($perms) => collect($perms)->sort()->values()->all() === $this->permisosAsistant())
                ->where('simulacion.activa', true)
                ->where('simulacion.disponible', true)
            );

        // "Inicio" lleva al panel del usuario mientras dura la simulación.
        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('asistant.panel'));

        // Es solo visual: el server no le quita los permisos reales.
        $this->assertTrue($admin->fresh()->hasRole('admin'));

        // "Ver como Admin" vuelve al dashboard con los roles reales.
        $this->actingAs($admin)
            ->post(route('ver-como-usuario.salir'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('user.roles', fn ($roles) => collect($roles)->contains('admin'))
                ->where('simulacion.activa', false)
                ->where('simulacion.disponible', true)
            );
    }

    public function test_un_usuario_comun_no_tiene_la_opcion(): void
    {
        $asistant = $this->usuarioConRol('asistant');

        $this->actingAs($asistant)
            ->post(route('ver-como-usuario.iniciar'))
            ->assertForbidden();

        $this->actingAs($asistant)
            ->get(route('asistant.panel'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('simulacion.activa', false)
                ->where('simulacion.disponible', false)
            );
    }

    public function test_invitado_no_puede_iniciar(): void
    {
        $this->post(route('ver-como-usuario.iniciar'))
            ->assertRedirect(route('login'));
    }
}

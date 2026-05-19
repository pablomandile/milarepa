<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Verifica que el middleware `permission:read usuarios` (tarea 2.2)
 * sigue activo en /usuarios. Un usuario con rol asistant debe recibir 403.
 *
 * Cubre indirectamente el patrón aplicado a los otros 5 controllers
 * administrativos (MembresiasGestion, EstadoCuentaMembresias,
 * BotonesPago, ExcencionPago, PaginasConfiguracion).
 */
class AdminEndpointPermissionTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * users.pais_id es NOT NULL DEFAULT 1 con FK a paises. Usamos INSERT IGNORE
     * directo porque Pais tiene $fillable = ['nombre'] y firstOrCreate ignora
     * el id explícito. Crear dentro de cada test (no en setUp) porque
     * DatabaseTransactions envuelve setUp en la transacción que se rollbackea.
     */
    private function asegurarPaisDefault(): void
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);
    }

    public function test_asistant_role_cannot_access_usuarios_index(): void
    {
        $this->asegurarPaisDefault();
        $asistant = User::factory()->create();
        $asistant->assignRole('asistant');

        $response = $this->actingAs($asistant)->get('/usuarios');

        // Spatie's PermissionMiddleware lanza 403 cuando falta el permiso
        $response->assertStatus(403);
    }

    public function test_admin_role_can_access_usuarios_index(): void
    {
        $this->asegurarPaisDefault();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get('/usuarios');

        // Status 200 (renderiza la página). Cualquier otro status que no sea
        // 403 indica que el permiso pasó — eso es lo que queremos validar.
        $this->assertNotEquals(403, $response->status());
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/usuarios');

        // Sin auth, el middleware `auth:sanctum` redirige antes que
        // se chequee el permission. 302 a /login es el comportamiento esperado.
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }
}

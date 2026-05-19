<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Verifica que las rutas /email-preview/{tipo}/{id} (tarea 1.2) ya no son
 * accesibles sin autenticación ni con un rol sin permisos.
 *
 * Vector original: anónimo podía hacer GET /email-preview/inscripcion/1
 * y ver datos PII de cualquier inscripción enumerando IDs.
 */
class EmailPreviewIdorTest extends TestCase
{
    use DatabaseTransactions;

    public function test_email_preview_with_id_requires_authentication(): void
    {
        $response = $this->get('/email-preview/inscripcion/1');

        // Sin auth, el middleware `auth:sanctum` redirige a /login.
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    public function test_email_preview_with_id_rejects_asistant_role(): void
    {
        // users.pais_id NOT NULL DEFAULT 1 → asegurar paises(1) existe.
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);
        $asistant = User::factory()->create();
        $asistant->assignRole('asistant');

        $response = $this->actingAs($asistant)->get('/email-preview/inscripcion/1');

        // El controller hace abort_unless(hasAnyRole(['admin','editor']), 403).
        $response->assertStatus(403);
    }

    public function test_preview_without_id_is_public(): void
    {
        // Las versiones SIN {id} usan datos dummy y siguen públicas.
        // Este test asegura que no rompimos esa funcionalidad.
        $response = $this->get('/email-preview/inscripcion');

        $response->assertStatus(200);
    }
}

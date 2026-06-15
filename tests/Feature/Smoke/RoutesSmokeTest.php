<?php

namespace Tests\Feature\Smoke;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Smoke tests: confirman que páginas index clave responden correctamente
 * (no 500 ni 403) para un usuario autenticado. Sirven como red de seguridad
 * rápida tras cualquier cambio. Solo se prueban rutas que requieren auth pero
 * no un permiso específico.
 */
class RoutesSmokeTest extends TestCase
{
    use DatabaseTransactions;

    private function usuario(): User
    {
        DB::statement(
            'INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())',
            ['Argentina'],
        );

        return User::factory()->create();
    }

    public function test_maestros_index_responde_ok(): void
    {
        $this->actingAs($this->usuario())->get(route('maestros.index'))->assertSuccessful();
    }

    public function test_libros_index_responde_ok(): void
    {
        $this->actingAs($this->usuario())->get(route('libros.index'))->assertSuccessful();
    }

    public function test_metodospago_index_responde_ok(): void
    {
        $this->actingAs($this->usuario())->get(route('metodospago.index'))->assertSuccessful();
    }
}

<?php

namespace Tests\Feature\ImageUpload;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Base para los tests de la subida de imagen diferida.
 * Usa la BD de test (DatabaseTransactions) y un disco "public" falso por test.
 */
abstract class ImageUploadTestCase extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /**
     * users.pais_id es NOT NULL DEFAULT 1 con FK a paises. Garantizamos que
     * exista el país por defecto antes de crear usuarios. Idempotente.
     */
    protected function asegurarPaisDefault(): void
    {
        DB::statement(
            'INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())',
            ['Argentina'],
        );
    }

    /**
     * Crea (dentro de la transacción del test) un usuario autenticable.
     * Las rutas de Maestro / Libro / MetodoPago solo requieren auth, no permisos.
     */
    protected function usuarioAutenticado(): User
    {
        $this->asegurarPaisDefault();

        return User::factory()->create();
    }
}

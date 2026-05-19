<?php

namespace Tests\Feature\Security;

use App\Support\UserLookupToken;
use Tests\TestCase;

/**
 * Verifica el comportamiento del token opaco (tarea 2.1b):
 *
 * 1. `UserLookupToken::issue($id)` produce un token cifrado.
 * 2. `resolveUserId()` devuelve el id original con un token válido.
 * 3. Token modificado/inválido/null → null (no falla, no expone info).
 * 4. Token vencido → null.
 *
 * Si alguien rompe el helper o cambia la lógica de cifrado/TTL, falla.
 */
class LookupTokenIdorTest extends TestCase
{
    public function test_issued_token_resolves_to_original_user_id(): void
    {
        $token = UserLookupToken::issue(42);

        $this->assertNotEmpty($token);
        $this->assertEquals(42, UserLookupToken::resolveUserId($token));
    }

    public function test_tampered_token_returns_null(): void
    {
        $token = UserLookupToken::issue(42);
        $tampered = substr($token, 0, -5) . 'XXXXX';

        $this->assertNull(UserLookupToken::resolveUserId($tampered));
    }

    public function test_garbage_string_returns_null(): void
    {
        $this->assertNull(UserLookupToken::resolveUserId('cualquier-cosa-no-cifrada'));
    }

    public function test_null_token_returns_null(): void
    {
        $this->assertNull(UserLookupToken::resolveUserId(null));
    }

    public function test_expired_token_returns_null(): void
    {
        // Generar manualmente un token con exp en el pasado para simular vencido.
        // Usa la misma lógica que UserLookupToken::issue pero con timestamp pasado.
        $payload = json_encode([
            'user_id' => 42,
            'exp' => now()->subMinutes(5)->timestamp,
        ]);
        $expired = \Illuminate\Support\Facades\Crypt::encryptString($payload);

        $this->assertNull(UserLookupToken::resolveUserId($expired));
    }
}

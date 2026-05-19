<?php

namespace Tests\Feature\Security;

use Tests\TestCase;

/**
 * Verifica que el middleware SecurityHeaders (tarea 3.1) sigue presente.
 * Si alguien elimina el middleware del Kernel o los headers del archivo,
 * este test falla.
 */
class SecurityHeadersTest extends TestCase
{
    public function test_response_includes_security_headers(): void
    {
        // Cualquier respuesta sirve — el middleware está en el grupo `web`
        // y aplica a todo. GET /welcome es público, no requiere setup.
        $response = $this->get('/welcome');

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }
}

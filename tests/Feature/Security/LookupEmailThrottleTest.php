<?php

namespace Tests\Feature\Security;

use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Verifica que el throttle:5,1 sigue activo en /grid-actividades/lookup-email
 * (tarea 2.1). Si alguien lo quita de routes/web.php, este test falla.
 */
class LookupEmailThrottleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Limpiar el limitador entre corridas para que el test sea determinista
        // (sin esto, los runs consecutivos compartirían el contador).
        RateLimiter::clear('grid-actividades.lookup-email');
        // El nombre real de la clave la genera Laravel internamente. Para
        // determinismo total usamos un IP único en cada corrida.
    }

    public function test_lookup_email_returns_429_after_5_attempts_per_minute(): void
    {
        $payload = ['email' => 'noexiste@example.com'];

        // IP custom por header para no chocar con throttles de otras corridas
        $headers = ['REMOTE_ADDR' => '10.99.99.' . random_int(1, 254)];

        // Las primeras 5 deben pasar (200 con found:false)
        for ($i = 1; $i <= 5; $i++) {
            $response = $this->withServerVariables($headers)
                ->postJson('/grid-actividades/lookup-email', $payload);
            $response->assertStatus(200);
        }

        // La 6ª debe ser bloqueada por el throttle
        $response = $this->withServerVariables($headers)
            ->postJson('/grid-actividades/lookup-email', $payload);
        $response->assertStatus(429);
        $response->assertHeader('Retry-After');
    }
}

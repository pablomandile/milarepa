<?php

namespace Tests\Feature\Security;

use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Verifica que el middleware SecurityHeaders (tarea 3.1) sigue presente.
 * Si alguien elimina el middleware del Kernel o los headers del archivo,
 * este test falla.
 *
 * También cubre la excepción para rutas embebibles (config/embed.php):
 * esas rutas no reciben X-Frame-Options y sí CSP frame-ancestors.
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

    public function test_grid_actividades_publica_sigue_bloqueando_iframes(): void
    {
        // La grilla pública original NO es embebible: debe conservar DENY
        // aunque exista la variante embebible /grillaembebida.
        $response = $this->get('/grid-actividades');

        $response->assertHeader('X-Frame-Options', 'DENY');
    }

    public function test_grilla_embebida_permite_iframe_desde_origenes_configurados(): void
    {
        $response = $this->get('/grillaembebida');

        $response->assertOk();
        $response->assertHeaderMissing('X-Frame-Options');
        $response->assertHeader(
            'Content-Security-Policy',
            "frame-ancestors 'self' https://meditarenargentina.org https://www.meditarenargentina.org"
        );
        // El resto de los headers de defensa en profundidad siguen presentes.
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('GridActividades/GrillaEmbebida')
            ->has('actividades')
            ->has('gridVariante'));
    }
}

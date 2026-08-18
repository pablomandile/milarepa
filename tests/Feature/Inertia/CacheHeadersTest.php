<?php

namespace Tests\Feature\Inertia;

use App\Http\Middleware\HandleInertiaRequests;
use Tests\TestCase;

/**
 * Una URL de Inertia devuelve dos cuerpos distintos según el header `X-Inertia`:
 * el HTML de arranque para una navegación, el JSON de la página para un XHR. Lo
 * único que se lo dice a una caché es `Vary: X-Inertia`, y el CDN de Hostinger lo
 * borra al comprimir con brotli. Con la URL pelada como clave de caché, el JSON de
 * una navegación interna pisa al HTML, y al restaurar una pestaña descartada Chrome
 * reusa esa entrada sin revalidar: el usuario ve el JSON crudo en pantalla.
 *
 * `no-cache` no alcanza — permite guardar y sólo obliga a revalidar, y la
 * navegación de historial saltea la revalidación.
 */
class CacheHeadersTest extends TestCase
{
    /** Sin esto el middleware contesta 409 y el test parece roto sin estarlo. */
    private function versionDeInertia(): string
    {
        return (string) app(HandleInertiaRequests::class)->version(request());
    }

    public function test_prohibe_guardar_la_respuesta_xhr_de_inertia(): void
    {
        $respuesta = $this->get('/login', [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $this->versionDeInertia(),
        ]);

        $respuesta->assertOk();
        $this->assertStringContainsString('application/json', (string) $respuesta->headers->get('Content-Type'));
        $this->assertStringContainsString('no-store', (string) $respuesta->headers->get('Cache-Control'));
    }

    /**
     * La otra mitad del invariante, y la que nadie extraña hasta que es tarde:
     * Chrome no guarda en bfcache un documento servido con `no-store`, así que
     * poner el header "en todas las respuestas, que es más simple" convierte cada
     * "atrás" en una ida completa a la red sin ningún síntoma que lo delate.
     */
    public function test_el_documento_html_sigue_siendo_cacheable(): void
    {
        $respuesta = $this->get('/login');

        $respuesta->assertOk();
        $this->assertStringContainsString('text/html', (string) $respuesta->headers->get('Content-Type'));
        $this->assertStringNotContainsString('no-store', (string) $respuesta->headers->get('Cache-Control'));
    }

    /** El header que el CDN borra, pero que igual tiene que salir del origen. */
    public function test_declara_vary_x_inertia(): void
    {
        $vary = (string) $this->get('/login')->headers->get('Vary');

        $this->assertStringContainsString('X-Inertia', $vary);
        $this->assertStringContainsString('Accept-Encoding', $vary);
    }
}

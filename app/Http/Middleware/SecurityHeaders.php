<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Agrega headers HTTP de defensa en profundidad a cada respuesta.
 *
 * - X-Frame-Options: DENY                                — bloquea iframes (anti-clickjacking)
 * - X-Content-Type-Options: nosniff                      — el browser no adivina MIME types
 * - Referrer-Policy: strict-origin-when-cross-origin     — limita info filtrada en el Referer
 * - Permissions-Policy: camera=(), microphone=(), geolocation=()
 *                                                          — deniega APIs sensibles del browser
 *
 * NO incluye Strict-Transport-Security (HSTS) ni Content-Security-Policy (CSP).
 * HSTS requiere validar 100% HTTPS estable en producción.
 * CSP requiere relevamiento por la combinación Inertia+Vite y se aborda como
 * tarea separada (3.1b en SECURITY_AUDIT.md).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        return $response;
    }
}

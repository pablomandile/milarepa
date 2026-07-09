<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Agrega headers HTTP de defensa en profundidad a cada respuesta.
 *
 * - X-Frame-Options: DENY                                — bloquea iframes (anti-clickjacking),
 *                                                          salvo en las rutas embebibles de
 *                                                          config/embed.php, que en su lugar reciben
 *                                                          Content-Security-Policy: frame-ancestors
 *                                                          con los orígenes permitidos
 * - X-Content-Type-Options: nosniff                      — el browser no adivina MIME types
 * - Referrer-Policy: strict-origin-when-cross-origin     — limita info filtrada en el Referer
 * - Permissions-Policy: camera=(), microphone=(), geolocation=()
 *                                                          — deniega APIs sensibles del browser
 *
 * NO incluye Strict-Transport-Security (HSTS) ni una CSP completa.
 * HSTS requiere validar 100% HTTPS estable en producción.
 * La CSP completa requiere relevamiento por la combinación Inertia+Vite y se
 * aborda como tarea separada (3.1b en SECURITY_AUDIT.md); acá solo se emite la
 * directiva frame-ancestors para las rutas embebibles.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->esEmbebible($request)) {
            // ALLOW-FROM está obsoleto: para permitir orígenes específicos se omite
            // X-Frame-Options y se usa CSP frame-ancestors (los browsers lo priorizan).
            $ancestors = trim(config('embed.frame_ancestors', ''));
            $response->headers->set('Content-Security-Policy', "frame-ancestors 'self' {$ancestors}");
        } else {
            $response->headers->set('X-Frame-Options', 'DENY');
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        return $response;
    }

    private function esEmbebible(Request $request): bool
    {
        $routes = config('embed.routes', []);

        return $routes !== [] && $request->routeIs(...$routes);
    }
}

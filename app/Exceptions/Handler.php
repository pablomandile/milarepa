<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // 419 por token CSRF expirado (típico al cerrar sesión tras inactividad):
        // en vez de mostrar "419 Page Expired", cerramos sesión limpio y redirigimos
        // al login con un aviso. Inertia sigue el redirect 302 de forma transparente.
        // Laravel convierte TokenMismatchException en HttpException(419) ANTES de
        // evaluar los renderable callbacks, por eso interceptamos por status code.
        $this->renderable(function (HttpExceptionInterface $e, $request) {
            if ($e->getStatusCode() !== 419) {
                return null;
            }

            // IMPORTANTE: NO destruimos la sesión acá. Un 419 es un token CSRF
            // vencido en ESTE request puntual (p. ej. el PUT de preferencia de tema
            // con la cookie XSRF apenas desfasada tras el login), NO necesariamente
            // una sesión muerta. Si la deslogueábamos, cualquier 419 de fondo —
            // silenciado por su .catch()— echaba al usuario sin que se diera cuenta.
            // Si la sesión sigue viva, /login (middleware guest) lo devuelve al
            // dashboard; si está realmente vencida, ve el login con el aviso.
            $destino = route('login', ['expired' => 1]);

            // En requests Inertia (XHR) forzamos recarga COMPLETA del navegador hacia
            // el login: garantiza una cookie XSRF-TOKEN fresca. Inertia::location
            // dispara window.location = ... .
            if ($request->header('X-Inertia')) {
                return Inertia::location($destino);
            }

            return redirect()->to($destino);
        });
    }
}

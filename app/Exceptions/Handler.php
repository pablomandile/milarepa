<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Auth;
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

            Auth::guard('web')->logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            // El aviso va por query param (?expired=1) y no por flash: el flash no
            // sobrevive la secuencia excepción -> recarga completa (se pierde al crearse
            // una sesión nueva). El login lo lee del query string.
            $destino = route('login', ['expired' => 1]);

            // Para requests Inertia (XHR) forzamos una recarga COMPLETA del navegador
            // hacia el login. Un redirect parcial dejaría la cookie XSRF-TOKEN
            // desincronizada con la sesión nueva (el login siguiente fallaría con 419
            // hasta hacer F5). Inertia::location dispara window.location = ... .
            if ($request->header('X-Inertia')) {
                return Inertia::location($destino);
            }

            return redirect()->to($destino);
        });
    }
}

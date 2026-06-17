<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Auth;
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
            return redirect()->route('login')
                ->with('status', 'Tu sesión expiró. Por favor, iniciá sesión de nuevo.');
        });
    }
}

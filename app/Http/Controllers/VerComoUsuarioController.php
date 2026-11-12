<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * "Ver como usuario": simulación rápida de cómo ve el sistema un usuario común
 * (rol asistant). Es solo visual: mientras está activa, HandleInertiaRequests
 * comparte el rol y los permisos del asistant en lugar de los reales, y el
 * dashboard manda al panel del usuario. Los permisos del server no cambian.
 */
class VerComoUsuarioController extends Controller
{
    public const SESSION_KEY = 'ver_como_usuario';

    public function iniciar(Request $request): RedirectResponse
    {
        abort_if($request->user()->hasRole('asistant'), 403);

        $request->session()->put(self::SESSION_KEY, true);

        return redirect()->route('asistant.panel');
    }

    public function salir(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('dashboard');
    }
}

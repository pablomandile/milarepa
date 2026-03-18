<?php

namespace App\Http\Controllers;

use App\Models\EnvioMail;
use App\Models\Entidad;
use App\Models\MembresiaUsuario;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class EnvioActividadesOnlineController extends Controller
{
    public function index()
    {
        $inicioMesActual = Carbon::now()->startOfMonth();

        $cantidadCandidatos = MembresiaUsuario::query()
            ->where('membresia_online', true)
            ->whereHas('user', function ($query) {
                $query->whereNotNull('email');
            })
            ->where(function ($query) use ($inicioMesActual) {
                $query->whereNull('envioActOnline')
                    ->orWhereDate('envioActOnline', '<', $inicioMesActual->toDateString());
            })
            ->count();

        return inertia('EnvioActividadesOnline/Index', [
            'mesActual' => ucfirst($inicioMesActual->locale('es')->translatedFormat('F Y')),
            'cantidadCandidatos' => $cantidadCandidatos,
        ]);
    }

    public function enviar(): RedirectResponse
    {
        $inicioMesActual = Carbon::now()->startOfMonth();
        $fechaEnvio = now()->toDateString();
        $mesMotivo = ucfirst(now()->locale('es')->translatedFormat('F'));
        $mesOnline = mb_strtoupper(now()->locale('es')->translatedFormat('F'), 'UTF-8');
        $entidadPrincipal = Entidad::where('entidad_principal', true)->first();
        $linkActividadesOnline = 'https://milarepa.com.ar/actividades-online';

        $candidatos = MembresiaUsuario::query()
            ->with('user:id,name,email')
            ->where('membresia_online', true)
            ->whereHas('user', function ($query) {
                $query->whereNotNull('email')
                    ->where('email', '!=', '');
            })
            ->where(function ($query) use ($inicioMesActual) {
                $query->whereNull('envioActOnline')
                    ->orWhereDate('envioActOnline', '<', $inicioMesActual->toDateString());
            })
            ->get();

        $enviados = 0;

        foreach ($candidatos as $membresiaUsuario) {
            $usuario = $membresiaUsuario->user;

            if (!$usuario || empty($usuario->email)) {
                continue;
            }

            Mail::send('emails.envio_Actividades_online', [
                'usuario' => $usuario,
                'nombrePracticante' => $usuario->name,
                'mesOnline' => $mesOnline,
                'linkActividadesOnline' => $linkActividadesOnline,
                'entidadPrincipal' => $entidadPrincipal,
                'esPreviewPrueba' => false,
            ], function ($message) use ($usuario, $mesOnline) {
                $message->to($usuario->email, $usuario->name)
                    ->subject('Actividades Online - ' . $mesOnline);
            });

            $membresiaUsuario->update([
                'envioActOnline' => $fechaEnvio,
            ]);

            EnvioMail::create([
                'fecha' => $fechaEnvio,
                'tipo' => 'Manual',
                'user_id' => auth()->id(),
                'destinatario' => $usuario->email,
                'motivo' => 'Actividades Online [' . $mesMotivo . ']',
            ]);

            $enviados++;
        }

        return redirect()
            ->route('envio-actividades-online.index')
            ->with('success', "Se enviaron {$enviados} emails de actividades online.");
    }
}

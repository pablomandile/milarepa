<?php

namespace App\Http\Controllers;

use App\Models\EmailEnvioConfiguracion;
use App\Models\EmailPlantilla;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmailEnvioConfiguracionesController extends Controller
{
    public function index()
    {
        $configuraciones = EmailEnvioConfiguracion::query()
            ->orderBy('id')
            ->get(['id', 'proceso_key', 'proceso_nombre', 'plantilla_archivo']);

        $emails = EmailPlantilla::query()
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'plantilla_archivo']);

        return inertia('EmailEnvioConfiguraciones/Index', [
            'configuraciones' => $configuraciones,
            'emails' => $emails,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $plantillasValidas = EmailPlantilla::query()->pluck('plantilla_archivo')->all();

        $validated = $request->validate([
            'configuraciones' => ['required', 'array'],
            'configuraciones.*.id' => ['required', 'integer', 'exists:email_envio_configuraciones,id'],
            'configuraciones.*.plantilla_archivo' => ['required', 'string', Rule::in($plantillasValidas)],
        ]);

        foreach ($validated['configuraciones'] as $item) {
            EmailEnvioConfiguracion::query()
                ->where('id', $item['id'])
                ->update([
                    'plantilla_archivo' => $item['plantilla_archivo'],
                ]);
        }

        return redirect()
            ->route('email-envio-configuraciones.index')
            ->with('success', 'Configuración de envíos actualizada con éxito.');
    }
}

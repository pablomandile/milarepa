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

        $plantillasArchivos = EmailPlantilla::listaPlantillasArchivo();

        return inertia('EmailEnvioConfiguraciones/Index', [
            'configuraciones' => $configuraciones,
            'plantillasArchivos' => $plantillasArchivos,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $plantillasValidas = EmailPlantilla::listaPlantillasArchivo();

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

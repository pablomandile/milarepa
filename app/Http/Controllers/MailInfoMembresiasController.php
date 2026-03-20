<?php

namespace App\Http\Controllers;

use App\Models\ConfiguracionSistema;
use App\Models\Imagen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MailInfoMembresiasController extends Controller
{
    private const CLAVE_IMAGEN_ID = 'mail_info_membresias_imagen_id';
    private const CLAVE_IMAGEN_RUTA = 'mail_info_membresias_imagen_ruta';

    public function index()
    {
        $imagenId = (int) ConfiguracionSistema::obtenerTexto(self::CLAVE_IMAGEN_ID, '0');
        $imagenRuta = ConfiguracionSistema::obtenerTexto(self::CLAVE_IMAGEN_RUTA, '');

        if ($imagenId > 0 && empty($imagenRuta)) {
            $imagen = Imagen::query()->find($imagenId);
            if ($imagen && !empty($imagen->ruta)) {
                $imagenRuta = '/storage/' . ltrim((string) $imagen->ruta, '/');
                ConfiguracionSistema::guardarTexto(self::CLAVE_IMAGEN_RUTA, $imagenRuta);
            }
        }

        return inertia('Configuracion/MailInfoMembresias', [
            'imagenId' => $imagenId > 0 ? $imagenId : null,
            'imagenUrl' => $imagenRuta,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'imagen_id' => ['nullable', 'integer', 'exists:imagenes,id'],
        ]);

        $imagenId = $validated['imagen_id'] ?? null;

        if (is_null($imagenId)) {
            ConfiguracionSistema::guardarTexto(self::CLAVE_IMAGEN_ID, null);
            ConfiguracionSistema::guardarTexto(self::CLAVE_IMAGEN_RUTA, null);

            return back()->with('success', 'Imagen de Mail Info Membresías eliminada.');
        }

        $imagen = Imagen::query()->findOrFail($imagenId);

        ConfiguracionSistema::guardarTexto(self::CLAVE_IMAGEN_ID, (string) $imagen->id);
        ConfiguracionSistema::guardarTexto(self::CLAVE_IMAGEN_RUTA, '/storage/' . ltrim((string) $imagen->ruta, '/'));

        return back()->with('success', 'Imagen de Mail Info Membresías guardada con éxito.');
    }
}

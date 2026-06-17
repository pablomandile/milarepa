<?php

namespace App\Http\Controllers;

use App\Models\Entidad;
use App\Models\Membresia;
use App\Services\ImportarMembresiasService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ImportarMembresiasController extends Controller
{
    public function __construct(private ImportarMembresiasService $service)
    {
    }

    public function index()
    {
        // Post/Redirect/Get: preview() y store() redirigen acá con los datos flasheados,
        // así la URL queda en un GET refrescable (evita MethodNotAllowed al recargar).
        return $this->renderVista(
            preview: session('importMembresiasPreview'),
            resumen: session('importMembresiasResumen'),
            entidadSeleccionada: session('importMembresiasEntidad'),
        );
    }

    public function preview(Request $request)
    {
        $validated = $request->validate([
            'archivo' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'entidad_id' => ['required', 'integer', 'exists:entidades,id'],
        ]);

        $preview = $this->service->previsualizar($request->file('archivo'), (int) $validated['entidad_id']);

        return redirect()
            ->route('configuracion.importar-membresias')
            ->with('importMembresiasPreview', $preview)
            ->with('importMembresiasEntidad', (int) $validated['entidad_id']);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'archivo' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'entidad_id' => ['required', 'integer', 'exists:entidades,id'],
        ]);

        $resumen = $this->service->importar($request->file('archivo'), (int) $validated['entidad_id']);
        $resumen['mensaje'] = "Importación finalizada: {$resumen['creados']} creados, {$resumen['actualizados']} actualizados, {$resumen['sin_cambios']} sin cambios, {$resumen['membresias_asignadas']} membresías asignadas, {$resumen['errores']} errores.";

        return redirect()
            ->route('configuracion.importar-membresias')
            ->with('importMembresiasResumen', $resumen)
            ->with('importMembresiasEntidad', (int) $validated['entidad_id']);
    }

    private function renderVista(?array $preview = null, ?array $resumen = null, ?int $entidadSeleccionada = null)
    {
        // Solo entidades cuyo nombre comienza con "Centro" (sedes principales).
        $entidades = Entidad::query()
            ->select('id', 'nombre', 'abreviacion')
            ->where('nombre', 'like', 'Centro%')
            ->orderBy('nombre')
            ->get();

        $membresiasQuery = Membresia::query()
            ->select('id', 'nombre', 'abreviacion', 'entidad_id')
            ->whereNotNull('abreviacion');

        if ($entidadSeleccionada) {
            $membresiasQuery->where('entidad_id', $entidadSeleccionada);
        }

        $membresias = $membresiasQuery->orderBy('abreviacion')->get();

        return Inertia::render('Configuracion/ImportarMembresias', [
            'entidades' => $entidades,
            'entidadSeleccionada' => $entidadSeleccionada,
            'membresiasConAbreviacion' => $membresias,
            'preview' => $preview,
            'resumen' => $resumen,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Services\ImportarInscripcionesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ImportarInscripcionesController extends Controller
{
    /** Carpeta (disco local = storage/app) donde se guardan los reportes de importación. */
    private const DIR_REPORTES = 'import-reports/inscripciones';

    public function __construct(private ImportarInscripcionesService $service)
    {
    }

    public function index()
    {
        // Post/Redirect/Get: preview() y store() redirigen acá con los datos flasheados,
        // así la URL queda en un GET refrescable (evita MethodNotAllowed al recargar).
        return $this->renderVista(
            preview: session('importPreview'),
            resumen: session('importResumen'),
            actividadSeleccionada: session('importActividad'),
        );
    }

    public function preview(Request $request)
    {
        $validated = $this->validar($request);
        $actividadId = (int) $validated['actividad_id'];

        $preview = $this->service->previsualizar($request->file('archivo'), $actividadId);

        return redirect()
            ->route('estadoinscripciones.importar')
            ->with('importPreview', $preview)
            ->with('importActividad', $actividadId);
    }

    public function store(Request $request)
    {
        $validated = $this->validar($request);
        $actividadId = (int) $validated['actividad_id'];

        $resumen = $this->service->importar($request->file('archivo'), $actividadId);
        $resumen['mensaje'] = "Importación finalizada: {$resumen['creadas']} inscripciones creadas, {$resumen['omitidas']} omitidas, {$resumen['errores']} con error.";

        $this->guardarReporte($resumen, $actividadId);

        return redirect()
            ->route('estadoinscripciones.importar')
            ->with('importResumen', $resumen)
            ->with('importActividad', $actividadId);
    }

    /** Descarga un reporte JSON guardado. */
    public function descargarReporte(string $archivo)
    {
        $ruta = $this->rutaReporteSegura($archivo);
        abort_unless($ruta && Storage::disk('local')->exists($ruta), 404);

        return Storage::disk('local')->download($ruta);
    }

    /** Elimina un reporte JSON. Solo administradores. */
    public function eliminarReporte(Request $request, string $archivo)
    {
        abort_unless($request->user()?->hasRole('admin'), 403, 'Solo un administrador puede borrar reportes.');

        $ruta = $this->rutaReporteSegura($archivo);
        if ($ruta && Storage::disk('local')->exists($ruta)) {
            Storage::disk('local')->delete($ruta);
        }

        return redirect()->route('estadoinscripciones.importar')->with('success', 'Reporte eliminado.');
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'archivo' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'actividad_id' => ['required', 'integer', 'exists:actividades,id'],
        ]);
    }

    private function renderVista(?array $preview = null, ?array $resumen = null, ?int $actividadSeleccionada = null)
    {
        $actividades = Actividad::query()
            ->with('entidad:id,abreviacion')
            ->select('id', 'nombre', 'fecha_inicio', 'entidad_id')
            ->orderBy('nombre')
            ->get();

        return Inertia::render('EstadoInscripciones/Importar', [
            'actividades' => $actividades,
            'actividadSeleccionada' => $actividadSeleccionada,
            'preview' => $preview,
            'resumen' => $resumen,
            'reportes' => $this->listarReportes(),
            'esAdmin' => (bool) request()->user()?->hasRole('admin'),
        ]);
    }

    /** Persiste el resumen + detalle de la importación como JSON (se conserva hasta borrado manual). */
    private function guardarReporte(array $resumen, int $actividadId): void
    {
        $actividad = Actividad::find($actividadId);

        $reporte = [
            'generado_en' => now()->toIso8601String(),
            'usuario' => request()->user()?->name,
            'actividad_id' => $actividadId,
            'actividad_nombre' => $actividad?->nombre,
            'resumen' => collect($resumen)->except('filas')->all(),
            'filas' => $resumen['filas'] ?? [],
        ];

        $nombre = now()->format('Y-m-d_His') . '_act' . $actividadId . '.json';
        Storage::disk('local')->put(
            self::DIR_REPORTES . '/' . $nombre,
            json_encode($reporte, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );
    }

    /** Lista los reportes guardados con metadata para la UI (más nuevos primero). */
    private function listarReportes(): array
    {
        $disk = Storage::disk('local');
        if (!$disk->exists(self::DIR_REPORTES)) {
            return [];
        }

        return collect($disk->files(self::DIR_REPORTES))
            ->filter(fn ($f) => str_ends_with($f, '.json'))
            ->sortDesc()
            ->map(function ($f) use ($disk) {
                $data = json_decode((string) $disk->get($f), true) ?: [];
                $r = $data['resumen'] ?? [];

                return [
                    'archivo' => basename($f),
                    'generado_en' => $data['generado_en'] ?? null,
                    'usuario' => $data['usuario'] ?? null,
                    'actividad_nombre' => $data['actividad_nombre'] ?? null,
                    'creadas' => $r['creadas'] ?? 0,
                    'omitidas' => $r['omitidas'] ?? 0,
                    'errores' => $r['errores'] ?? 0,
                    'columnas_desconocidas' => $r['columnas_desconocidas'] ?? [],
                ];
            })
            ->values()
            ->all();
    }

    /** Valida el nombre de archivo (evita path traversal) y devuelve la ruta relativa o null. */
    private function rutaReporteSegura(string $archivo): ?string
    {
        if (!preg_match('/^[\w\-]+\.json$/', $archivo)) {
            return null;
        }

        return self::DIR_REPORTES . '/' . $archivo;
    }
}

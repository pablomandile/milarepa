<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Services\ImportarMultieventoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ImportarMultieventoController extends Controller
{
    /** Carpeta (disco local = storage/app) donde se guardan los reportes. */
    private const DIR_REPORTES = 'import-reports/multievento';

    public function __construct(private ImportarMultieventoService $service)
    {
    }

    public function index(Request $request)
    {
        $this->autorizar($request);

        // Post/Redirect/Get: preview() y store() redirigen acá con los datos flasheados.
        return $this->renderVista(
            preview: session('mePreview'),
            resumen: session('meResumen'),
        );
    }

    public function preview(Request $request)
    {
        $this->autorizar($request);
        $this->validar($request);

        $contenido = $this->obtenerContenido($request);
        $preview = $this->service->previsualizar($contenido, $this->mapeoDe($request));

        return redirect()
            ->route('estadoinscripciones.importar-multievento')
            ->with('mePreview', $preview);
    }

    public function store(Request $request)
    {
        $this->autorizar($request);
        $this->validar($request);

        $contenido = $this->obtenerContenido($request);
        $resumen = $this->service->importar($contenido, $this->mapeoDe($request));
        $resumen['mensaje'] = "Importación finalizada: {$resumen['creadas']} creadas, {$resumen['actualizadas']} actualizadas, "
            . "{$resumen['omitidas']} omitidas, {$resumen['sin_actividad']} sin actividad, "
            . "{$resumen['descartadas_fecha']} fuera de fecha, {$resumen['errores']} con error.";

        $this->guardarReporte($resumen);

        return redirect()
            ->route('estadoinscripciones.importar-multievento')
            ->with('meResumen', $resumen);
    }

    /** Descarga un reporte JSON guardado. */
    public function descargarReporte(Request $request, string $archivo)
    {
        $this->autorizar($request);
        $ruta = $this->rutaReporteSegura($archivo);
        abort_unless($ruta && Storage::disk('local')->exists($ruta), 404);

        return Storage::disk('local')->download($ruta);
    }

    /** Elimina un reporte JSON. Solo administradores. */
    public function eliminarReporte(Request $request, string $archivo)
    {
        abort_unless($request->user()?->hasRole(['Admin', 'admin']), 403, 'Solo un administrador puede borrar reportes.');

        $ruta = $this->rutaReporteSegura($archivo);
        if ($ruta && Storage::disk('local')->exists($ruta)) {
            Storage::disk('local')->delete($ruta);
        }

        return redirect()->route('estadoinscripciones.importar-multievento')->with('success', 'Reporte eliminado.');
    }

    private function autorizar(Request $request): void
    {
        $user = $request->user();
        if (!$user || !$user->hasRole(['Admin', 'Editor', 'admin', 'editor'])) {
            abort(403);
        }
    }

    private function validar(Request $request): void
    {
        $request->validate([
            'archivo'   => ['nullable', 'file', 'mimes:csv,txt', 'max:10240'],
            'desde_url' => ['nullable', 'boolean'],
            'mapeo'     => ['nullable'],
        ]);

        if (!$request->boolean('desde_url') && !$request->hasFile('archivo')) {
            throw ValidationException::withMessages([
                'archivo' => 'Subí un archivo CSV o elegí importar desde la planilla.',
            ]);
        }
    }

    /** Obtiene el CSV: desde la planilla configurada (URL) o desde el archivo subido. */
    private function obtenerContenido(Request $request): string
    {
        if ($request->boolean('desde_url')) {
            try {
                return $this->service->descargarPlanilla();
            } catch (\Throwable $e) {
                throw ValidationException::withMessages(['desde_url' => $e->getMessage()]);
            }
        }

        return (string) file_get_contents($request->file('archivo')->getRealPath());
    }

    /**
     * Normaliza el mapeo evento→actividad_id recibido (JSON o array), descartando vacíos.
     *
     * @return array<string,int>
     */
    private function mapeoDe(Request $request): array
    {
        $raw = $request->input('mapeo');
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?: [];
        }
        if (!is_array($raw)) {
            return [];
        }

        $mapeo = [];
        foreach ($raw as $clave => $actividadId) {
            if ($actividadId !== null && $actividadId !== '' && (int) $actividadId > 0) {
                $mapeo[(string) $clave] = (int) $actividadId;
            }
        }

        return $mapeo;
    }

    private function renderVista(?array $preview = null, ?array $resumen = null)
    {
        $actividades = Actividad::query()
            ->with('entidad:id,abreviacion')
            ->select('id', 'nombre', 'fecha_inicio', 'entidad_id')
            ->orderByDesc('fecha_inicio')
            ->get();

        return Inertia::render('EstadoInscripciones/ImportarMultievento', [
            'actividades' => $actividades,
            'preview'     => $preview,
            'resumen'     => $resumen,
            'sheetUrl'    => $this->service->urlPlanilla(),
            'reportes'    => $this->listarReportes(),
            'esAdmin'     => (bool) request()->user()?->hasRole(['Admin', 'admin']),
        ]);
    }

    /** Persiste el resumen + detalle de la importación como JSON. */
    private function guardarReporte(array $resumen): void
    {
        $reporte = [
            'generado_en' => now()->toIso8601String(),
            'usuario'     => request()->user()?->name,
            'resumen'     => collect($resumen)->except(['filas'])->all(),
            'filas'       => $resumen['filas'] ?? [],
        ];

        $nombre = now()->format('Y-m-d_His') . '_multievento.json';
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
                    'archivo'           => basename($f),
                    'generado_en'       => $data['generado_en'] ?? null,
                    'usuario'           => $data['usuario'] ?? null,
                    'creadas'           => $r['creadas'] ?? 0,
                    'actualizadas'      => $r['actualizadas'] ?? 0,
                    'omitidas'          => $r['omitidas'] ?? 0,
                    'sin_actividad'     => $r['sin_actividad'] ?? 0,
                    'descartadas_fecha' => $r['descartadas_fecha'] ?? 0,
                    'errores'           => $r['errores'] ?? 0,
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

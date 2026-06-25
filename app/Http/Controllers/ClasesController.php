<?php

namespace App\Http\Controllers;

use App\Concerns\ProcesaImagenAlGuardar;
use App\Http\Requests\ClaseRequest;
use App\Services\OptimizadorImagenService;
use Illuminate\Http\Request;
use App\Models\Ciclo;
use App\Models\Clase;
use App\Models\Coordinador;
use App\Models\Descripcion;
use App\Models\Entidad;
use App\Models\EsquemaPrecio;
use App\Models\Imagen;
use App\Models\Maestro;
use App\Models\Modalidad;
use App\Models\PaginaActividadOnline;
use App\Models\Stream;
use Carbon\Carbon;
use Illuminate\Support\Str;

class ClasesController extends Controller
{
    use ProcesaImagenAlGuardar;

    /**
     * Nombre de la clase destacada que siempre se muestra primero en la página
     * pública (es una actividad especial que se repite con este mismo nombre).
     */
    private const CLASE_DESTACADA = 'meditaciones guiadas de 30 minutos';

    public function index()
    {
        $clases = Clase::with(['ciclo', 'entidad', 'maestros', 'coordinadores', 'esquemaPrecio', 'modalidad', 'stream', 'imagen'])
            ->orderByDesc('created_at')
            ->get();

        return inertia('Clases/Index', ['clases' => $clases]);
    }

    /**
     * Esquemas de precios para el select del formulario de clase:
     * "Actividad Gratuita" siempre primero; el resto, del más reciente al más antiguo.
     */
    private function esquemaPreciosParaFormulario()
    {
        return EsquemaPrecio::with(['membresias.membresia', 'membresias.moneda', 'membresias.botonPago'])
            ->orderByRaw("CASE WHEN LOWER(nombre) = 'actividad gratuita' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Nombres de clases ya usados (distintos, orden ascendente) para sugerir en el formulario.
     */
    private function nombresClasesUsados()
    {
        return Clase::query()
            ->whereNotNull('nombre')
            ->where('nombre', '!=', '')
            ->select('nombre')
            ->distinct()
            ->orderBy('nombre')
            ->pluck('nombre');
    }

    public function create()
    {
        return inertia('Clases/Create', [
            'ciclos' => Ciclo::orderBy('nombre')->get(),
            'entidades' => Entidad::orderBy('nombre')->get(),
            'maestros' => Maestro::orderBy('nombre')->get(),
            'coordinadores' => Coordinador::orderBy('nombre')->get(),
            'esquemaPrecios' => $this->esquemaPreciosParaFormulario(),
            'modalidades' => Modalidad::orderBy('nombre')->get(),
            'streams' => Stream::with('links')->orderBy('nombre')->get(),
            'imagenes' => Imagen::orderByDesc('created_at')->get(['id', 'nombre', 'ruta']),
            'nombresClases' => $this->nombresClasesUsados(),
        ]);
    }

    public function store(ClaseRequest $request, OptimizadorImagenService $optimizador)
    {
        $validated = $request->validated();
        unset($validated['imagen']); // archivo: se procesa aparte, no es columna
        $maestrosIds = $validated['maestro_ids'] ?? [];
        $coordinadoresIds = $validated['coordinador_ids'] ?? [];
        unset($validated['maestro_ids'], $validated['coordinador_ids']);

        $this->guardarConImagen($request->file('imagen'), 'img/clases', $optimizador, function ($imagenId) use ($validated, $maestrosIds, $coordinadoresIds) {
            if ($imagenId) {
                $validated['imagen_id'] = $imagenId;
            }
            $clase = Clase::create($validated);
            $clase->maestros()->sync($maestrosIds);
            $clase->coordinadores()->sync($coordinadoresIds);
            return $clase;
        });

        return redirect()->route('clases.index');
    }

    public function show(string $id)
    {
        //
    }

    public function showPublic(Request $request, Clase $clase)
    {
        $clase->load(['imagen', 'entidad', 'maestros.imagen', 'coordinadores', 'ciclo']);
        $descripciones = Descripcion::query()
            ->where('nombre', 'Clase PG')
            ->orWhere('nombre', 'like', 'Estructura de una sesi%')
            ->get();

        $descripcionClasePg = $descripciones->firstWhere('nombre', 'Clase PG');
        $descripcionEstructuraSesion = $descripciones->first(function ($item) {
            return str_starts_with((string) $item->nombre, 'Estructura de una sesi');
        });

        return inertia('Clases/ShowPublic', [
            'clase' => $clase,
            'returnUrl' => $request->query('return_url'),
            'descripcionesCards' => [
                'clasePg' => $descripcionClasePg,
                'estructuraSesion' => $descripcionEstructuraSesion,
            ],
        ]);
    }

    /**
     * Página pública de Clases (todas las entidades). Mismo formato que Actividades
     * Online: banner + tarjetas por clase con su cronograma (fecha, horario y título
     * de cada sesión). Para el público general NO se exponen los links de stream.
     * Los botones filtran por entidad.
     */
    public function paginaPublica()
    {
        // El banner reutiliza la imagen de la página de Actividades Online del mes en curso.
        $monthStart = now()->startOfMonth();
        $monthKey = $monthStart->format('Y-m');
        $pagina = PaginaActividadOnline::with('imagen')
            ->where('mes_referencia', $monthKey)
            ->latest('id')
            ->first();

        $monthLabel = ucfirst($monthStart->locale('es')->translatedFormat('F'));
        $cycleName = Ciclo::query()
            ->where('mes', (int) $monthStart->format('n'))
            ->orderBy('id')
            ->value('nombre');

        // Las clases de la entidad principal se muestran siempre primero.
        $entidadPrincipalId = Entidad::where('entidad_principal', true)->orderBy('id')->value('id');

        $clases = Clase::query()
            ->where('activa', true)
            ->with(['imagen', 'entidad:id,nombre', 'maestros:id,nombre', 'esquemaPrecio.membresias.membresia'])
            // Orden: entidad principal primero, luego la clase destacada, luego alfabético.
            ->when($entidadPrincipalId, fn ($q) => $q->orderByRaw('CASE WHEN entidad_id = ? THEN 0 ELSE 1 END', [$entidadPrincipalId]))
            ->orderByRaw('CASE WHEN LOWER(TRIM(nombre)) = ? THEN 0 ELSE 1 END', [self::CLASE_DESTACADA])
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'entidad_id', 'esquema_precio_id', 'mes_referencia', 'dias_semana', 'horario_desde', 'horario_hasta', 'titulos_por_fecha', 'imagen_id']);

        $clasesData = $clases->map(fn (Clase $clase) => [
            'id' => $clase->id,
            'nombre' => $clase->nombre,
            'entidad_id' => $clase->entidad_id,
            'entidad' => $clase->entidad?->nombre,
            'mes_referencia' => $clase->mes_referencia,
            'image_url' => $clase->imagen ? '/storage/' . $clase->imagen->ruta : null,
            'dias_semana' => collect($clase->dias_semana ?? [])->map(fn ($d) => (string) $d)->values(),
            'maestros' => $clase->maestros->pluck('nombre')->filter()->values(),
            'horario_label' => $this->horarioLabel($clase),
            'precio' => $this->precioPublicoClase($clase),
            'fechas' => $this->sesionesDeClase($clase),
        ])->values();

        // Entidades con clases activas, para los botones de filtro
        // (entidad principal primero, el resto alfabético).
        $entidades = $clases->map->entidad
            ->filter()
            ->unique('id')
            ->sortBy(fn ($entidad) => [$entidad->id === $entidadPrincipalId ? 0 : 1, mb_strtolower((string) $entidad->nombre)])
            ->map(fn ($entidad) => ['id' => $entidad->id, 'nombre' => $entidad->nombre])
            ->values();

        return inertia('Paginas/Clases', [
            'headerImageUrl' => $pagina?->imagen ? '/storage/' . $pagina->imagen->ruta : null,
            'monthLabel' => $monthLabel,
            'cycleName' => $cycleName,
            'entidades' => $entidades,
            'clases' => $clasesData,
        ]);
    }

    /**
     * Genera las sesiones (fechas) de una clase dentro de su mes_referencia, a partir
     * de sus dias_semana, con el horario y el título cargado para cada fecha.
     */
    private function sesionesDeClase(Clase $clase): array
    {
        $weekdayMap = [
            1 => 'lunes', 2 => 'martes', 3 => 'miercoles', 4 => 'jueves',
            5 => 'viernes', 6 => 'sabado', 7 => 'domingo',
        ];

        $mes = (string) $clase->mes_referencia;
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes)) {
            return [];
        }

        $diasSemana = collect($clase->dias_semana ?? [])->map(fn ($d) => (string) $d);
        if ($diasSemana->isEmpty()) {
            return [];
        }

        $hora = $clase->horario_desde ? Carbon::parse($clase->horario_desde)->format('H:i') : null;
        $monthStart = Carbon::createFromFormat('Y-m-d', $mes . '-01')->startOfMonth();
        $monthEnd = $monthStart->copy()->endOfMonth();

        $fechas = [];
        for ($cursor = $monthStart->copy(); $cursor->lte($monthEnd); $cursor->addDay()) {
            $weekday = $weekdayMap[$cursor->dayOfWeekIso] ?? null;
            if (!$weekday || !$diasSemana->contains($weekday)) {
                continue;
            }

            $fecha = $cursor->toDateString();
            $titulo = data_get($clase->titulos_por_fecha, $fecha);

            $fechas[] = [
                'fecha' => $fecha,
                'hora' => $hora,
                'titulo_fecha' => filled($titulo) ? (string) $titulo : null,
            ];
        }

        return $fechas;
    }

    /**
     * "17:00 a 17:30 hs" a partir de horario_desde / horario_hasta.
     */
    private function horarioLabel(Clase $clase): ?string
    {
        $desde = $clase->horario_desde ? Carbon::parse($clase->horario_desde)->format('H:i') : null;
        $hasta = $clase->horario_hasta ? Carbon::parse($clase->horario_hasta)->format('H:i') : null;

        if ($desde && $hasta) {
            return "{$desde} a {$hasta} hs";
        }

        return $desde ? "{$desde} hs" : ($hasta ? "{$hasta} hs" : null);
    }

    /**
     * Precio público de la clase: "GRATIS" cuando el esquema es "Actividad Gratuita"
     * (o el precio sin membresía es 0); si no, el precio "Sin membresía" formateado.
     */
    private function precioPublicoClase(Clase $clase): array
    {
        $esquema = $clase->esquemaPrecio;

        if (!$esquema) {
            return ['es_gratis' => false, 'label' => null];
        }

        if (Str::contains($this->normalizarNombre($esquema->nombre), 'actividad gratuita')) {
            return ['es_gratis' => true, 'label' => 'GRATIS'];
        }

        $membresias = $esquema->membresias ?? collect();
        $sinMembresia = $membresias->first(fn ($m) => Str::contains($this->normalizarNombre(optional($m->membresia)->nombre), 'sin membresia'));
        $precio = $sinMembresia ? (float) $sinMembresia->precio : (float) ($membresias->max('precio') ?? 0);

        if ($precio <= 0) {
            return ['es_gratis' => true, 'label' => 'GRATIS'];
        }

        return ['es_gratis' => false, 'label' => '$' . number_format($precio, 0, ',', '.')];
    }

    private function normalizarNombre(?string $valor): string
    {
        $normalizado = Str::lower(trim((string) $valor));
        return strtr($normalizado, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);
    }

    public function edit($id)
    {
        $clase = Clase::with(['imagen', 'maestros', 'coordinadores'])->findOrFail($id);

        return inertia('Clases/Edit', [
            'clase' => $clase,
            'ciclos' => Ciclo::orderBy('nombre')->get(),
            'entidades' => Entidad::orderBy('nombre')->get(),
            'maestros' => Maestro::orderBy('nombre')->get(),
            'coordinadores' => Coordinador::orderBy('nombre')->get(),
            'esquemaPrecios' => $this->esquemaPreciosParaFormulario(),
            'modalidades' => Modalidad::orderBy('nombre')->get(),
            'streams' => Stream::with('links')->orderBy('nombre')->get(),
            'imagenes' => Imagen::orderByDesc('created_at')->get(['id', 'nombre', 'ruta']),
            'nombresClases' => $this->nombresClasesUsados(),
        ]);
    }

    public function update(ClaseRequest $request, $id, OptimizadorImagenService $optimizador)
    {
        $validated = $request->validated();
        unset($validated['imagen']); // archivo: se procesa aparte, no es columna
        $maestrosIds = $validated['maestro_ids'] ?? [];
        $coordinadoresIds = $validated['coordinador_ids'] ?? [];
        unset($validated['maestro_ids'], $validated['coordinador_ids']);

        $clase = Clase::findOrFail($id);

        $this->guardarConImagen($request->file('imagen'), 'img/clases', $optimizador, function ($imagenId) use ($clase, $validated, $maestrosIds, $coordinadoresIds) {
            if ($imagenId) {
                $validated['imagen_id'] = $imagenId;
            }
            $clase->update($validated);
            $clase->maestros()->sync($maestrosIds);
            $clase->coordinadores()->sync($coordinadoresIds);
            return $clase;
        });

        return redirect()->route('clases.index');
    }

    public function destroy($id)
    {
        try {
            $clase = Clase::findOrFail($id);
            $clase->delete();

            return redirect()->route('clases.index')->with('success', 'Clase eliminada con exito.');
        } catch (\Throwable $e) {
            return redirect()->route('clases.index')->with('error', 'Error al eliminar la clase: ' . $e->getMessage());
        }
    }

    public function updateEstado(Request $request, Clase $clase)
    {
        $validated = $request->validate([
            'activa' => ['required', 'boolean'],
        ]);

        $clase->activa = (bool) $validated['activa'];
        $clase->save();

        return back()->with('success', 'Estado de la clase actualizado.');
    }
}


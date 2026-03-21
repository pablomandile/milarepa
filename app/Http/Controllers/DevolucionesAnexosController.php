<?php

namespace App\Http\Controllers;

use App\Http\Requests\DevolucionAnexoRequest;
use App\Models\DevolucionAnexo;
use App\Models\Entidad;
use App\Models\HistoricoPedidoLibro;
use App\Models\InventarioEntidadLibro;
use App\Models\Libro;
use App\Models\PrestamoAnexo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Inertia\Response;
use Illuminate\Validation\ValidationException;

class DevolucionesAnexosController extends Controller
{
    public function index(): Response
    {
        $devoluciones = DevolucionAnexo::with([
            'devolvedor:id,nombre',
            'prestador:id,nombre',
            'libro:id,titulo',
            'usuario:id,name',
        ])->latest('fecha')->latest('id')->get();

        return inertia('DevolucionesAnexos/Index', [
            'devoluciones' => $devoluciones,
        ]);
    }

    public function create(): Response
    {
        $entidadPrincipal = $this->obtenerEntidadPrincipal();

        return inertia('DevolucionesAnexos/Create', [
            'entidades' => $this->obtenerEntidades(),
            'entidadPrincipal' => $entidadPrincipal,
            'libros' => $this->obtenerLibrosConStockEnNoPrincipal(),
            'authUser' => $this->obtenerAuthUser(),
        ]);
    }

    public function store(DevolucionAnexoRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $entidadPrincipal = $this->obtenerEntidadPrincipal();

        if ($entidadPrincipal) {
            $validated['prestador_id'] = $entidadPrincipal['id'];
        }

        $devolvedorId = (int) ($validated['devolvedor_id'] ?? 0);
        $libroId = (int) ($validated['libro_id'] ?? 0);
        $cantidad = (int) ($validated['cantidad'] ?? 0);
        $entidadPrincipalId = (int) ($entidadPrincipal['id'] ?? 0);

        $validated['user_id'] = auth()->id();

        DB::transaction(function () use ($validated, $devolvedorId, $libroId, $cantidad, $entidadPrincipalId) {
            $inventarioDevolvedor = InventarioEntidadLibro::query()
                ->where('entidad_id', $devolvedorId)
                ->where('libro_id', $libroId)
                ->lockForUpdate()
                ->first();

            $stockDeEntidad = (int) ($inventarioDevolvedor?->cantidad ?? 0);

            if ($cantidad < 1 || $cantidad > $stockDeEntidad) {
                throw ValidationException::withMessages([
                    'cantidad' => 'La cantidad no puede exceder el stock disponible en la entidad devolvedora (' . $stockDeEntidad . ').',
                ]);
            }

            $inventarioDevolvedor->update([
                'cantidad' => $stockDeEntidad - $cantidad,
            ]);

            $inventarioPrincipal = InventarioEntidadLibro::query()
                ->where('entidad_id', $entidadPrincipalId)
                ->where('libro_id', $libroId)
                ->lockForUpdate()
                ->first();

            if (!$inventarioPrincipal) {
                $inventarioPrincipal = InventarioEntidadLibro::create([
                    'entidad_id' => $entidadPrincipalId,
                    'libro_id' => $libroId,
                    'cantidad' => 0,
                ]);
            }

            $inventarioPrincipal->update([
                'cantidad' => (int) $inventarioPrincipal->cantidad + $cantidad,
            ]);

            DevolucionAnexo::create($validated);
        });

        return redirect()->route('inventario-libros.devoluciones-anexos.index')
            ->with('success', 'Devolución anexa registrada correctamente.');
    }

    public function librosPorEntidad(Request $request)
    {
        $request->validate([
            'entidad_id' => ['required', 'integer', 'exists:entidades,id'],
        ]);

        $entidadId = $request->integer('entidad_id');
        $entidadPrincipalId = (int) ($this->obtenerEntidadPrincipal()['id'] ?? 0);

        $libros = Libro::query()->orderBy('titulo')->get(['id', 'titulo'])
            ->map(function ($libro) use ($entidadId, $entidadPrincipalId) {
                return [
                    'id' => (int) $libro->id,
                    'titulo' => $libro->titulo,
                    'stock_disponible' => $this->obtenerStockDisponibleEntidadLibro($entidadId, (int) $libro->id, $entidadPrincipalId),
                ];
            })
            ->filter(fn ($libro) => (int) $libro['stock_disponible'] > 0)
            ->values();

        return response()->json([
            'libros' => $libros,
        ]);
    }

    private function obtenerEntidades(): Collection
    {
        return Entidad::query()->orderBy('nombre')->get(['id', 'nombre']);
    }

    private function obtenerLibrosConStockEnNoPrincipal(): Collection
    {
        $entidadPrincipalId = (int) ($this->obtenerEntidadPrincipal()['id'] ?? 0);
        $libros = Libro::query()->orderBy('titulo')->get(['id', 'titulo']);

        return $libros->map(function ($libro) use ($entidadPrincipalId) {
            return [
                'id' => (int) $libro->id,
                'titulo' => $libro->titulo,
                'stock_disponible_no_principal' => $this->obtenerStockTotalNoPrincipalPorLibro((int) $libro->id, $entidadPrincipalId),
            ];
        })->filter(fn ($libro) => (int) $libro['stock_disponible_no_principal'] > 0)->values();
    }

    private function obtenerStockTotalNoPrincipalPorLibro(int $libroId, int $entidadPrincipalId): int
    {
        return (int) InventarioEntidadLibro::query()
            ->where('libro_id', $libroId)
            ->where('entidad_id', '!=', $entidadPrincipalId)
            ->sum('cantidad');
    }

    private function obtenerStockDisponibleEntidadLibro(int $entidadId, int $libroId, int $entidadPrincipalId): int
    {
        if ($entidadId <= 0 || $libroId <= 0) {
            return 0;
        }

        if ($entidadPrincipalId > 0 && $entidadId === $entidadPrincipalId) {
            return (int) InventarioEntidadLibro::query()
                ->where('entidad_id', $entidadId)
                ->where('libro_id', $libroId)
                ->value('cantidad');
        }

        return (int) InventarioEntidadLibro::query()
            ->where('entidad_id', $entidadId)
            ->where('libro_id', $libroId)
            ->value('cantidad');
    }

    private function obtenerAuthUser(): ?array
    {
        $authUser = auth()->user();

        return $authUser ? [
            'id' => $authUser->id,
            'name' => $authUser->name,
        ] : null;
    }

    private function obtenerEntidadPrincipal(): ?array
    {
        $entidadPrincipal = Entidad::query()
            ->where('entidad_principal', true)
            ->first(['id', 'nombre']);

        return $entidadPrincipal ? [
            'id' => $entidadPrincipal->id,
            'nombre' => $entidadPrincipal->nombre,
        ] : null;
    }
}

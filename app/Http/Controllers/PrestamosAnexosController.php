<?php

namespace App\Http\Controllers;

use App\Http\Requests\PrestamoAnexoRequest;
use App\Models\Entidad;
use App\Models\InventarioEntidadLibro;
use App\Models\Libro;
use App\Models\PrestamoAnexo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

class PrestamosAnexosController extends Controller
{
    public function index(): Response
    {
        $prestamos = PrestamoAnexo::with([
            'prestadora:id,nombre',
            'receptora:id,nombre',
            'libro:id,titulo',
            'usuario:id,name',
        ])->latest('fecha')->latest('id')->get();

        return inertia('PrestamosAnexos/Index', [
            'prestamos' => $prestamos,
        ]);
    }

    public function create(): Response
    {
        $entidadPrincipal = $this->obtenerEntidadPrincipal();

        return inertia('PrestamosAnexos/Create', [
            'entidades' => $this->obtenerEntidades(),
            'entidadPrincipal' => $entidadPrincipal,
            'libros' => $this->obtenerLibrosConInventario(),
            'authUser' => $this->obtenerAuthUser(),
        ]);
    }

    public function store(PrestamoAnexoRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $entidadPrincipal = $this->obtenerEntidadPrincipal();

        if ($entidadPrincipal) {
            $validated['prestadora_id'] = $entidadPrincipal['id'];
        }

        $validated['user_id'] = auth()->id();

        DB::transaction(function () use ($validated, $entidadPrincipal) {
            $principalId = (int) ($entidadPrincipal['id'] ?? 0);
            $receptoraId = (int) ($validated['receptora_id'] ?? 0);
            $libroId = (int) ($validated['libro_id'] ?? 0);
            $cantidad = (int) ($validated['cantidad'] ?? 0);

            $inventarioPrincipal = InventarioEntidadLibro::query()
                ->where('entidad_id', $principalId)
                ->where('libro_id', $libroId)
                ->lockForUpdate()
                ->first();

            if (!$inventarioPrincipal || (int) $inventarioPrincipal->cantidad < $cantidad) {
                $disponible = (int) ($inventarioPrincipal?->cantidad ?? 0);
                throw ValidationException::withMessages([
                    'cantidad' => 'La cantidad no puede exceder el stock disponible de la entidad principal (' . $disponible . ').',
                ]);
            }

            $inventarioPrincipal->update([
                'cantidad' => (int) $inventarioPrincipal->cantidad - $cantidad,
            ]);

            $inventarioReceptora = InventarioEntidadLibro::query()
                ->where('entidad_id', $receptoraId)
                ->where('libro_id', $libroId)
                ->lockForUpdate()
                ->first();

            if (!$inventarioReceptora) {
                $inventarioReceptora = InventarioEntidadLibro::create([
                    'entidad_id' => $receptoraId,
                    'libro_id' => $libroId,
                    'cantidad' => 0,
                ]);
            }

            $inventarioReceptora->update([
                'cantidad' => (int) $inventarioReceptora->cantidad + $cantidad,
            ]);

            PrestamoAnexo::create($validated);
        });

        return redirect()->route('inventario-libros.prestamos-anexos.index')
            ->with('success', 'Préstamo anexo registrado correctamente.');
    }

    public function edit(PrestamoAnexo $prestamo_anexo): Response
    {
        $entidadPrincipal = $this->obtenerEntidadPrincipal();

        return inertia('PrestamosAnexos/Edit', [
            'prestamo' => $prestamo_anexo,
            'entidades' => $this->obtenerEntidades(),
            'entidadPrincipal' => $entidadPrincipal,
            'libros' => $this->obtenerLibrosConInventario((int) $prestamo_anexo->libro_id),
            'authUser' => $this->obtenerAuthUser(),
        ]);
    }

    public function update(PrestamoAnexoRequest $request, PrestamoAnexo $prestamo_anexo): RedirectResponse
    {
        $validated = $request->validated();
        $entidadPrincipal = $this->obtenerEntidadPrincipal();

        if ($entidadPrincipal) {
            $validated['prestadora_id'] = $entidadPrincipal['id'];
        }

        $validated['user_id'] = auth()->id();

        $prestamo_anexo->update($validated);

        return redirect()->route('inventario-libros.prestamos-anexos.index')
            ->with('success', 'Préstamo anexo actualizado correctamente.');
    }

    public function destroy(PrestamoAnexo $prestamo_anexo): RedirectResponse
    {
        $prestamo_anexo->delete();

        return redirect()->route('inventario-libros.prestamos-anexos.index')
            ->with('success', 'Préstamo anexo eliminado correctamente.');
    }

    private function obtenerEntidades(): Collection
    {
        return Entidad::query()->orderBy('nombre')->get(['id', 'nombre']);
    }

    private function obtenerLibrosConInventario(?int $incluirLibroId = null): Collection
    {
        $principalId = (int) ($this->obtenerEntidadPrincipal()['id'] ?? 0);

        if ($principalId <= 0) {
            return collect();
        }

        $inventariosPrincipal = InventarioEntidadLibro::query()
            ->where('entidad_id', $principalId)
            ->where(function ($query) use ($incluirLibroId) {
                $query->where('cantidad', '>', 0);

                if ($incluirLibroId) {
                    $query->orWhere('libro_id', $incluirLibroId);
                }
            })
            ->get(['libro_id', 'cantidad'])
            ->keyBy('libro_id');

        $librosIds = $inventariosPrincipal->keys()->all();

        return Libro::query()
            ->whereIn('id', $librosIds)
            ->orderBy('titulo')
            ->get(['id', 'titulo'])
            ->map(function ($libro) use ($inventariosPrincipal) {
                return [
                    'id' => (int) $libro->id,
                    'titulo' => $libro->titulo,
                    'stock_disponible' => (int) ($inventariosPrincipal[(int) $libro->id]->cantidad ?? 0),
                ];
            })
            ->values();
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

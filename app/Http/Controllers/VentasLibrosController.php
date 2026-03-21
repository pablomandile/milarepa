<?php

namespace App\Http\Controllers;

use App\Http\Requests\VentaLibroRequest;
use App\Models\Entidad;
use App\Models\HistoricoPedidoLibro;
use App\Models\InventarioEntidadLibro;
use App\Models\Venta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

class VentasLibrosController extends Controller
{
    public function index(): Response
    {
        return inertia('VentasLibros/Index', [
            'entidades' => Entidad::query()->orderBy('nombre')->get(['id', 'nombre']),
            'ventas' => Venta::query()
                ->with(['entidad:id,nombre', 'libro:id,titulo', 'comprobante:id,ruta', 'vendedor:id,name'])
                ->latest('fecha')
                ->latest('id')
                ->get(),
            'modosPago' => ['Efectivo', 'Transferencia', 'Tarjeta'],
        ]);
    }

    public function librosPorEntidad(Request $request)
    {
        $request->validate([
            'entidad_id' => ['required', 'integer', 'exists:entidades,id'],
        ]);

        $entidadId = $request->integer('entidad_id');

        $libros = InventarioEntidadLibro::query()
            ->with('libro:id,titulo,precio')
            ->where('entidad_id', $entidadId)
            ->where('cantidad', '>', 0)
            ->orderByDesc('cantidad')
            ->get()
            ->map(function ($inventario) {
                return [
                    'id' => (int) $inventario->libro_id,
                    'titulo' => $inventario->libro?->titulo ?? 'Sin libro',
                    'precio_unitario' => (float) ($inventario->libro?->precio ?? 0),
                    'stock_disponible' => (int) $inventario->cantidad,
                ];
            })
            ->values();

        return response()->json([
            'libros' => $libros,
        ]);
    }

    public function store(VentaLibroRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            $entidadId = (int) ($validated['entidad_id'] ?? 0);
            $libroId = (int) ($validated['libro_id'] ?? 0);
            $cantidad = (int) ($validated['cantidad'] ?? 0);
            $precioUnitario = (float) ($validated['precio_unitario'] ?? 0);

            $inventario = InventarioEntidadLibro::query()
                ->where('entidad_id', $entidadId)
                ->where('libro_id', $libroId)
                ->lockForUpdate()
                ->first();

            if (!$inventario || (int) $inventario->cantidad < $cantidad) {
                $disponible = (int) ($inventario?->cantidad ?? 0);

                throw ValidationException::withMessages([
                    'cantidad' => 'La cantidad no puede exceder el stock disponible de la entidad (' . $disponible . ').',
                ]);
            }

            $cantidadInicialEntidad = (int) $inventario->cantidad;
            $cantidadFinalEntidad = $cantidadInicialEntidad - $cantidad;

            $inventario->update([
                'cantidad' => $cantidadFinalEntidad,
            ]);

            $cantidadTotal = (int) InventarioEntidadLibro::query()
                ->where('libro_id', $libroId)
                ->lockForUpdate()
                ->sum('cantidad');

            $montoTotal = round($precioUnitario * $cantidad, 2);

            Venta::create([
                'fecha' => $validated['fecha'],
                'entidad_id' => $entidadId,
                'libro_id' => $libroId,
                'cantidad' => $cantidad,
                'precio_unitario' => $precioUnitario,
                'montoTotal' => $montoTotal,
                'modo' => $validated['modo'],
                'comprobante_id' => $validated['comprobante_id'] ?? null,
                'vendedor_id' => auth()->id(),
            ]);

            HistoricoPedidoLibro::create([
                'fecha' => $validated['fecha'],
                'libro_id' => $libroId,
                'entidad_id' => $entidadId,
                'cantidad_total' => $cantidadTotal,
                'cantidad_inicial' => $cantidadInicialEntidad,
                'cantidad_vendida' => $cantidad,
                'cantidad_final' => $cantidadFinalEntidad,
                'importe' => $montoTotal,
                'vendedor_id' => auth()->id(),
                'email_comprador' => null,
            ]);
        });

        return redirect()->route('inventario-libros.ventas.index')
            ->with('success', 'Venta registrada correctamente.');
    }
}

<?php

namespace App\Services;

use App\Models\ArticuloTienda;
use App\Models\InventarioEntidadArticuloTienda;
use Illuminate\Validation\ValidationException;

/**
 * Punto único para listar y mover stock de la Tienda general. A diferencia de
 * ProductoTharpaService (4 categorías = 4 tablas), acá hay UN solo catálogo
 * (articulos_tienda) con categorías dinámicas (categorias_tienda), así que el
 * filtro de categoría es por FK, no por modelo. Sin histórico (a diferencia de libros).
 * El descuento/devolución corre dentro de una transacción del llamador (usa lockForUpdate).
 */
class ProductoTiendaService
{
    /**
     * Artículos de la categoría con stock disponible (> 0) en la entidad dada.
     * @return array<int, array{id:int,titulo:string,tipo:?string,precio:float,stock_disponible:int}>
     */
    public function listarPorEntidad(int $categoriaId, int $entidadId): array
    {
        return InventarioEntidadArticuloTienda::query()
            ->with('articuloTienda')
            ->where('entidad_id', $entidadId)
            ->where('cantidad', '>', 0)
            ->whereHas('articuloTienda', fn ($q) => $q->where('categoria_tienda_id', $categoriaId))
            ->orderByDesc('cantidad')
            ->get()
            ->map(function (InventarioEntidadArticuloTienda $inventario) {
                $articulo = $inventario->articuloTienda;

                return [
                    'id' => (int) $inventario->articulo_tienda_id,
                    'titulo' => $articulo?->titulo ?? 'Sin artículo',
                    'tipo' => null,
                    'precio' => (float) ($articulo?->precio ?? 0),
                    'stock_disponible' => (int) $inventario->cantidad,
                ];
            })
            ->values()
            ->all();
    }

    /** Precio de catálogo del artículo (fuente de verdad server-side). */
    public function precio(int $articuloId): float
    {
        return (float) (ArticuloTienda::query()->whereKey($articuloId)->value('precio') ?? 0);
    }

    /** Devuelve el modelo de catálogo (para título/descripción). */
    public function buscarProducto(int $articuloId): ?ArticuloTienda
    {
        return ArticuloTienda::query()->find($articuloId);
    }

    /**
     * Descuenta `cantidad` del inventario del artículo para la entidad (con lockForUpdate).
     * $ctx no se usa hoy (sin histórico); se mantiene por paridad con ProductoTharpaService.
     */
    public function descontarStock(int $articuloId, int $entidadId, int $cantidad, array $ctx = []): void
    {
        $inventario = InventarioEntidadArticuloTienda::query()
            ->where('entidad_id', $entidadId)
            ->where('articulo_tienda_id', $articuloId)
            ->lockForUpdate()
            ->first();

        if (!$inventario || (int) $inventario->cantidad < $cantidad) {
            $disponible = (int) ($inventario?->cantidad ?? 0);

            throw ValidationException::withMessages([
                'items' => "Stock insuficiente para el artículo de tienda seleccionado (disponible: {$disponible}).",
            ]);
        }

        $inventario->update(['cantidad' => (int) $inventario->cantidad - $cantidad]);
    }

    /** Devuelve `cantidad` al inventario (crea la fila si no existe). Inverso de descontarStock. */
    public function devolverStock(int $articuloId, int $entidadId, int $cantidad, array $ctx = []): void
    {
        $inventario = InventarioEntidadArticuloTienda::query()
            ->where('entidad_id', $entidadId)
            ->where('articulo_tienda_id', $articuloId)
            ->lockForUpdate()
            ->first();

        if (!$inventario) {
            $inventario = InventarioEntidadArticuloTienda::create([
                'entidad_id' => $entidadId,
                'articulo_tienda_id' => $articuloId,
                'cantidad' => 0,
            ]);
        }

        $inventario->update(['cantidad' => (int) $inventario->cantidad + $cantidad]);
    }
}

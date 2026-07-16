<?php

namespace App\Services;

use App\Models\Arte;
use App\Models\HistoricoPedidoLibro;
use App\Models\InventarioEntidadArte;
use App\Models\InventarioEntidadLibro;
use App\Models\InventarioEntidadOracion;
use App\Models\InventarioEntidadOtro;
use App\Models\Libro;
use App\Models\Oracion;
use App\Models\Otro;
use Illuminate\Validation\ValidationException;

/**
 * Punto único para listar y mover stock de las 4 categorías de producto Tharpa
 * (libro / oracion / arte / otro). Lo usan el POS y el checkout de clases para no
 * duplicar la lógica de inventario por categoría. El descuento/devolución corre
 * dentro de una transacción del llamador (usa lockForUpdate).
 */
class ProductoTharpaService
{
    /** categoria => [modeloCatalogo, modeloInventario, fkProducto]. La relación del inventario al catálogo se llama igual que la categoría. */
    private const CATEGORIAS = [
        'libro' => [Libro::class, InventarioEntidadLibro::class, 'libro_id'],
        'oracion' => [Oracion::class, InventarioEntidadOracion::class, 'oracion_id'],
        'arte' => [Arte::class, InventarioEntidadArte::class, 'arte_id'],
        'otro' => [Otro::class, InventarioEntidadOtro::class, 'otro_id'],
    ];

    /** @return string[] */
    public function categorias(): array
    {
        return array_keys(self::CATEGORIAS);
    }

    /** @return array{0:class-string,1:class-string,2:string} [catalogo, inventario, fk] */
    private function resolver(string $categoria): array
    {
        if (!isset(self::CATEGORIAS[$categoria])) {
            throw new \InvalidArgumentException("Categoría de producto inválida: {$categoria}");
        }

        return self::CATEGORIAS[$categoria];
    }

    /**
     * Items del catálogo con stock disponible (> 0) en la entidad dada.
     * @return array<int, array{id:int,titulo:string,tipo:?string,precio:float,stock_disponible:int}>
     */
    public function listarPorEntidad(string $categoria, int $entidadId): array
    {
        [, $inventarioClass, $fk] = $this->resolver($categoria);
        $relacion = $categoria; // libro|oracion|arte|otro

        return $inventarioClass::query()
            ->with($relacion)
            ->where('entidad_id', $entidadId)
            ->where('cantidad', '>', 0)
            ->orderByDesc('cantidad')
            ->get()
            ->map(function ($inventario) use ($fk, $relacion) {
                $producto = $inventario->{$relacion};

                return [
                    'id' => (int) $inventario->{$fk},
                    'titulo' => $producto?->titulo ?? 'Sin producto',
                    'tipo' => $producto?->tipo,
                    'precio' => (float) ($producto?->precio ?? 0),
                    'stock_disponible' => (int) $inventario->cantidad,
                ];
            })
            ->values()
            ->all();
    }

    /** Precio de catálogo del producto (fuente de verdad server-side). */
    public function precio(string $categoria, int $productoId): float
    {
        [$catalogoClass] = $this->resolver($categoria);

        return (float) ($catalogoClass::query()->whereKey($productoId)->value('precio') ?? 0);
    }

    /** Devuelve el modelo de catálogo (para título/descripción). */
    public function buscarProducto(string $categoria, int $productoId)
    {
        [$catalogoClass] = $this->resolver($categoria);

        return $catalogoClass::query()->find($productoId);
    }

    /**
     * Descuenta `cantidad` del inventario de la categoría para la entidad (con lockForUpdate).
     * Para libros registra además el movimiento en `historico_pedidos_libros`.
     * $ctx: ['fecha'=>, 'vendedor_id'=>, 'email_comprador'=>].
     */
    public function descontarStock(string $categoria, int $productoId, int $entidadId, int $cantidad, array $ctx = []): void
    {
        [, $inventarioClass, $fk] = $this->resolver($categoria);

        $inventario = $inventarioClass::query()
            ->where('entidad_id', $entidadId)
            ->where($fk, $productoId)
            ->lockForUpdate()
            ->first();

        if (!$inventario || (int) $inventario->cantidad < $cantidad) {
            $disponible = (int) ($inventario?->cantidad ?? 0);

            throw ValidationException::withMessages([
                'items' => "Stock insuficiente para el producto seleccionado (disponible: {$disponible}).",
            ]);
        }

        $cantidadInicial = (int) $inventario->cantidad;
        $cantidadFinal = $cantidadInicial - $cantidad;
        $inventario->update(['cantidad' => $cantidadFinal]);

        if ($categoria === 'libro') {
            $this->historicoLibro($productoId, $entidadId, $cantidadInicial, $cantidad, $cantidadFinal, $ctx);
        }
    }

    /**
     * Devuelve `cantidad` al inventario (crea la fila si no existe). Inverso de descontarStock.
     * Para libros registra un movimiento de histórico con importe negativo.
     */
    public function devolverStock(string $categoria, int $productoId, int $entidadId, int $cantidad, array $ctx = []): void
    {
        [, $inventarioClass, $fk] = $this->resolver($categoria);

        $inventario = $inventarioClass::query()
            ->where('entidad_id', $entidadId)
            ->where($fk, $productoId)
            ->lockForUpdate()
            ->first();

        if (!$inventario) {
            $inventario = $inventarioClass::create([
                'entidad_id' => $entidadId,
                $fk => $productoId,
                'cantidad' => 0,
            ]);
        }

        $cantidadInicial = (int) $inventario->cantidad;
        $cantidadFinal = $cantidadInicial + $cantidad;
        $inventario->update(['cantidad' => $cantidadFinal]);

        if ($categoria === 'libro') {
            $this->historicoLibro($productoId, $entidadId, $cantidadInicial, $cantidad, $cantidadFinal, $ctx, devolucion: true);
        }
    }

    private function historicoLibro(int $libroId, int $entidadId, int $cantidadInicial, int $cantidad, int $cantidadFinal, array $ctx, bool $devolucion = false): void
    {
        $cantidadTotal = (int) InventarioEntidadLibro::query()
            ->where('libro_id', $libroId)
            ->lockForUpdate()
            ->sum('cantidad');

        $precio = (float) (Libro::query()->whereKey($libroId)->value('precio') ?? 0);
        $importe = ($devolucion ? -1 : 1) * ($precio * $cantidad);

        HistoricoPedidoLibro::create([
            'fecha' => $ctx['fecha'] ?? now(),
            'libro_id' => $libroId,
            'entidad_id' => $entidadId > 0 ? $entidadId : null,
            'cantidad_total' => $cantidadTotal,
            'cantidad_inicial' => $cantidadInicial,
            'cantidad_vendida' => $cantidad,
            'cantidad_final' => $cantidadFinal,
            'importe' => round($importe, 2),
            'vendedor_id' => $ctx['vendedor_id'] ?? auth()->id(),
            'email_comprador' => $ctx['email_comprador'] ?? null,
        ]);
    }
}

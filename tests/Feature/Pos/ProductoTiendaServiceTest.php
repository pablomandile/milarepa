<?php

namespace Tests\Feature\Pos;

use App\Models\ArticuloTienda;
use App\Models\CategoriaTienda;
use App\Models\Entidad;
use App\Models\InventarioEntidadArticuloTienda;
use App\Services\ProductoTiendaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * ProductoTiendaService: listado con stock por categoría y movimiento de inventario.
 */
class ProductoTiendaServiceTest extends TestCase
{
    use DatabaseTransactions;

    private function entidad(): Entidad
    {
        return Entidad::where('entidad_principal', true)->firstOrFail();
    }

    private function service(): ProductoTiendaService
    {
        return app(ProductoTiendaService::class);
    }

    public function test_listar_por_entidad_filtra_por_categoria_y_stock(): void
    {
        $ent = $this->entidad();
        $catA = CategoriaTienda::create(['nombre' => 'Cat A ' . uniqid()]);
        $catB = CategoriaTienda::create(['nombre' => 'Cat B ' . uniqid()]);

        $conStock = ArticuloTienda::create(['titulo' => 'Con stock', 'categoria_tienda_id' => $catA->id, 'precio' => 100]);
        $sinStock = ArticuloTienda::create(['titulo' => 'Sin stock', 'categoria_tienda_id' => $catA->id, 'precio' => 100]);
        $otraCat = ArticuloTienda::create(['titulo' => 'Otra cat', 'categoria_tienda_id' => $catB->id, 'precio' => 100]);

        InventarioEntidadArticuloTienda::create(['entidad_id' => $ent->id, 'articulo_tienda_id' => $conStock->id, 'cantidad' => 3]);
        InventarioEntidadArticuloTienda::create(['entidad_id' => $ent->id, 'articulo_tienda_id' => $sinStock->id, 'cantidad' => 0]);
        InventarioEntidadArticuloTienda::create(['entidad_id' => $ent->id, 'articulo_tienda_id' => $otraCat->id, 'cantidad' => 5]);

        $lista = $this->service()->listarPorEntidad($catA->id, $ent->id);

        $ids = array_column($lista, 'id');
        $this->assertContains($conStock->id, $ids);
        $this->assertNotContains($sinStock->id, $ids, 'No debe listar artículos sin stock');
        $this->assertNotContains($otraCat->id, $ids, 'No debe listar artículos de otra categoría');
    }

    public function test_descontar_y_devolver_stock(): void
    {
        $ent = $this->entidad();
        $cat = CategoriaTienda::create(['nombre' => 'Cat ' . uniqid()]);
        $articulo = ArticuloTienda::create(['titulo' => 'Zafu', 'categoria_tienda_id' => $cat->id, 'precio' => 4000]);
        InventarioEntidadArticuloTienda::create(['entidad_id' => $ent->id, 'articulo_tienda_id' => $articulo->id, 'cantidad' => 10]);

        $this->service()->descontarStock($articulo->id, $ent->id, 4);
        $this->assertSame(6, (int) InventarioEntidadArticuloTienda::where('articulo_tienda_id', $articulo->id)->value('cantidad'));

        $this->service()->devolverStock($articulo->id, $ent->id, 2);
        $this->assertSame(8, (int) InventarioEntidadArticuloTienda::where('articulo_tienda_id', $articulo->id)->value('cantidad'));
    }

    public function test_descontar_sin_stock_lanza_excepcion(): void
    {
        $ent = $this->entidad();
        $cat = CategoriaTienda::create(['nombre' => 'Cat ' . uniqid()]);
        $articulo = ArticuloTienda::create(['titulo' => 'Adorno', 'categoria_tienda_id' => $cat->id, 'precio' => 900]);
        InventarioEntidadArticuloTienda::create(['entidad_id' => $ent->id, 'articulo_tienda_id' => $articulo->id, 'cantidad' => 1]);

        $this->expectException(ValidationException::class);
        $this->service()->descontarStock($articulo->id, $ent->id, 5);
    }
}

<?php

namespace Tests\Feature\Pos;

use App\Models\ArticuloTienda;
use App\Models\CategoriaTienda;
use App\Models\Entidad;
use App\Models\InventarioEntidadArticuloTienda;
use App\Models\MetodoPago;
use App\Models\User;
use App\Models\VentaPos;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * POS: venta de un artículo de la Tienda general (descuenta su inventario y cobra
 * sobre la línea del ticket, igual que oración/arte/otro).
 */
class PosVentaArticuloTiendaTest extends TestCase
{
    use DatabaseTransactions;

    private function usuario(): User
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);

        return User::factory()->create();
    }

    private function entidad(): Entidad
    {
        return Entidad::where('entidad_principal', true)->firstOrFail();
    }

    private function metodo(): MetodoPago
    {
        return MetodoPago::firstOrCreate(['nombre' => 'Efectivo'], ['descripcion' => 'Efectivo', 'tipo_de_pago' => 'Presencial']);
    }

    private function articuloConStock(Entidad $ent, float $precio, int $stock): ArticuloTienda
    {
        $categoria = CategoriaTienda::create(['nombre' => 'Sahumerios ' . uniqid()]);
        $articulo = ArticuloTienda::create([
            'titulo' => 'Sahumerio de Sándalo',
            'categoria_tienda_id' => $categoria->id,
            'precio' => $precio,
        ]);
        InventarioEntidadArticuloTienda::create([
            'entidad_id' => $ent->id,
            'articulo_tienda_id' => $articulo->id,
            'cantidad' => $stock,
        ]);

        return $articulo;
    }

    public function test_venta_de_articulo_tienda_descuenta_stock_y_cobra_sobre_la_linea(): void
    {
        $ent = $this->entidad();
        $metodo = $this->metodo();
        $articulo = $this->articuloConStock($ent, 1500, 6);

        $resp = $this->actingAs($this->usuario())->post(route('pos.store'), [
            'entidad_id' => $ent->id,
            'metodo_pago_id' => $metodo->id,
            'items' => [['tipo' => 'articulo_tienda', 'producto_id' => $articulo->id, 'cantidad' => 2]],
        ]);

        $resp->assertRedirect(route('pos.index'));
        $this->assertSame(4, (int) InventarioEntidadArticuloTienda::where('articulo_tienda_id', $articulo->id)->value('cantidad'));

        $venta = VentaPos::latest('id')->first();
        $this->assertEquals(3000, (float) $venta->total);

        $item = $venta->items()->first();
        $this->assertSame('articulo_tienda', $item->tipo);
        $this->assertSame('articulo_tienda', $item->vendible_type);
        $this->assertSame($articulo->id, (int) $item->vendible_id);
        $this->assertEquals(3000, (float) $item->subtotal);
        $this->assertNotNull($item->cobro_id);

        // Cobro sobre la línea (venta_pos_item), con referencia y origen POS.
        $this->assertDatabaseHas('cobros', [
            'id' => $item->cobro_id,
            'cobrable_type' => 'venta_pos_item',
            'cobrable_id' => $item->id,
            'origen' => 'pos',
            'referencia' => "Por POS #{$venta->id}",
            'monto' => 3000,
        ]);
    }

    public function test_rollback_si_articulo_tienda_no_tiene_stock(): void
    {
        $ent = $this->entidad();
        $metodo = $this->metodo();
        $articulo = $this->articuloConStock($ent, 500, 1);
        $ventasPosAntes = VentaPos::count();

        $resp = $this->from(route('pos.index'))->actingAs($this->usuario())->post(route('pos.store'), [
            'entidad_id' => $ent->id,
            'metodo_pago_id' => $metodo->id,
            'items' => [['tipo' => 'articulo_tienda', 'producto_id' => $articulo->id, 'cantidad' => 5]], // excede stock (1)
        ]);

        $resp->assertSessionHasErrors('items');
        $this->assertSame($ventasPosAntes, VentaPos::count());
        $this->assertSame(1, (int) InventarioEntidadArticuloTienda::where('articulo_tienda_id', $articulo->id)->value('cantidad'));
    }
}

<?php

namespace Tests\Feature\Pos;

use App\Models\Arte;
use App\Models\Entidad;
use App\Models\HistoricoPedidoLibro;
use App\Models\InventarioEntidadArte;
use App\Models\InventarioEntidadLibro;
use App\Models\InventarioEntidadOracion;
use App\Models\Libro;
use App\Models\MetodoPago;
use App\Models\Oracion;
use App\Models\User;
use App\Models\Venta;
use App\Models\VentaPos;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Punto de venta (POS): venta de productos de las 4 categorías Tharpa con cobro por ítem.
 */
class PosVentaTest extends TestCase
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

    public function test_venta_de_oracion_descuenta_stock_y_cobra_sobre_la_linea(): void
    {
        $ent = $this->entidad();
        $metodo = $this->metodo();
        $oracion = Oracion::create(['titulo' => 'Refugio', 'tipo' => 'Librillo', 'precio' => 1000]);
        InventarioEntidadOracion::create(['entidad_id' => $ent->id, 'oracion_id' => $oracion->id, 'cantidad' => 5]);

        $resp = $this->actingAs($this->usuario())->post(route('pos.store'), [
            'entidad_id' => $ent->id,
            'metodo_pago_id' => $metodo->id,
            'items' => [['tipo' => 'oracion', 'producto_id' => $oracion->id, 'cantidad' => 2]],
        ]);

        $resp->assertRedirect(route('pos.index'));
        $this->assertSame(3, (int) InventarioEntidadOracion::where('oracion_id', $oracion->id)->value('cantidad'));

        $venta = VentaPos::latest('id')->first();
        $this->assertEquals(2000, (float) $venta->total);
        $item = $venta->items()->first();
        $this->assertSame('oracion', $item->tipo);
        $this->assertEquals(2000, (float) $item->subtotal);
        $this->assertNotNull($item->cobro_id);

        // Cobro sobre la línea (venta_pos_item), con referencia y origen POS.
        $this->assertDatabaseHas('cobros', [
            'id' => $item->cobro_id,
            'cobrable_type' => 'venta_pos_item',
            'cobrable_id' => $item->id,
            'origen' => 'pos',
            'referencia' => "Por POS #{$venta->id}",
            'monto' => 2000,
        ]);
    }

    public function test_venta_de_libro_crea_venta_e_historico(): void
    {
        $ent = $this->entidad();
        $metodo = $this->metodo();
        $libro = Libro::create(['titulo' => 'Libro POS', 'tipo' => 'Físico', 'precio' => 5000]);
        InventarioEntidadLibro::create(['entidad_id' => $ent->id, 'libro_id' => $libro->id, 'cantidad' => 4]);
        $ventasAntes = Venta::count();
        $histAntes = HistoricoPedidoLibro::where('libro_id', $libro->id)->count();

        $resp = $this->actingAs($this->usuario())->post(route('pos.store'), [
            'entidad_id' => $ent->id,
            'metodo_pago_id' => $metodo->id,
            'items' => [['tipo' => 'libro', 'producto_id' => $libro->id, 'cantidad' => 1]],
        ]);

        $resp->assertRedirect(route('pos.index'));
        $this->assertSame(3, (int) InventarioEntidadLibro::where('libro_id', $libro->id)->value('cantidad'));
        $this->assertSame($ventasAntes + 1, Venta::count());
        $this->assertSame($histAntes + 1, HistoricoPedidoLibro::where('libro_id', $libro->id)->count());

        $venta = VentaPos::latest('id')->first();
        $item = $venta->items()->first();
        // El cobro del libro va sobre el modelo Venta.
        $this->assertDatabaseHas('cobros', [
            'id' => $item->cobro_id,
            'cobrable_type' => 'venta',
            'origen' => 'pos',
            'monto' => 5000,
        ]);
    }

    public function test_multi_item_total_es_la_suma_de_cobros(): void
    {
        $ent = $this->entidad();
        $metodo = $this->metodo();
        $oracion = Oracion::create(['titulo' => 'O', 'tipo' => 'Audio', 'precio' => 1000]);
        InventarioEntidadOracion::create(['entidad_id' => $ent->id, 'oracion_id' => $oracion->id, 'cantidad' => 5]);
        $arte = Arte::create(['titulo' => 'A', 'tipo' => 'Tarjeta A5', 'precio' => 500]);
        InventarioEntidadArte::create(['entidad_id' => $ent->id, 'arte_id' => $arte->id, 'cantidad' => 5]);

        $resp = $this->actingAs($this->usuario())->post(route('pos.store'), [
            'entidad_id' => $ent->id,
            'metodo_pago_id' => $metodo->id,
            'items' => [
                ['tipo' => 'oracion', 'producto_id' => $oracion->id, 'cantidad' => 2], // 2000
                ['tipo' => 'arte', 'producto_id' => $arte->id, 'cantidad' => 3],        // 1500
            ],
        ]);

        $resp->assertRedirect(route('pos.index'));
        $venta = VentaPos::latest('id')->first();
        $this->assertEquals(3500, (float) $venta->total);
        $sumaCobros = (float) DB::table('cobros')->where('referencia', "Por POS #{$venta->id}")->sum('monto');
        $this->assertEquals(3500, $sumaCobros);
        $this->assertSame(2, $venta->items()->count());
    }

    public function test_rollback_si_un_item_no_tiene_stock(): void
    {
        $ent = $this->entidad();
        $metodo = $this->metodo();
        $oracion = Oracion::create(['titulo' => 'OK', 'tipo' => 'Audio', 'precio' => 1000]);
        InventarioEntidadOracion::create(['entidad_id' => $ent->id, 'oracion_id' => $oracion->id, 'cantidad' => 5]);
        $arte = Arte::create(['titulo' => 'SinStock', 'tipo' => 'Tarjeta A6', 'precio' => 500]);
        InventarioEntidadArte::create(['entidad_id' => $ent->id, 'arte_id' => $arte->id, 'cantidad' => 1]);

        $ventasPosAntes = VentaPos::count();

        $resp = $this->from(route('pos.index'))->actingAs($this->usuario())->post(route('pos.store'), [
            'entidad_id' => $ent->id,
            'metodo_pago_id' => $metodo->id,
            'items' => [
                ['tipo' => 'oracion', 'producto_id' => $oracion->id, 'cantidad' => 2],
                ['tipo' => 'arte', 'producto_id' => $arte->id, 'cantidad' => 3], // excede stock (1)
            ],
        ]);

        $resp->assertSessionHasErrors('items');
        // Rollback total: nada persiste, el stock de la oración queda intacto.
        $this->assertSame($ventasPosAntes, VentaPos::count());
        $this->assertSame(5, (int) InventarioEntidadOracion::where('oracion_id', $oracion->id)->value('cantidad'));
        $this->assertSame(0, (int) DB::table('cobros')->where('origen', 'pos')->count());
    }
}

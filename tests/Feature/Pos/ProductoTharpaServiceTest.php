<?php

namespace Tests\Feature\Pos;

use App\Models\Arte;
use App\Models\Entidad;
use App\Models\HistoricoPedidoLibro;
use App\Models\InventarioEntidadArte;
use App\Models\InventarioEntidadLibro;
use App\Models\InventarioEntidadOracion;
use App\Models\Libro;
use App\Models\Oracion;
use App\Services\ProductoTharpaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Servicio compartido de stock de productos Tharpa (POS + checkout de clases).
 */
class ProductoTharpaServiceTest extends TestCase
{
    use DatabaseTransactions;

    private function service(): ProductoTharpaService
    {
        return app(ProductoTharpaService::class);
    }

    private function entidad(): Entidad
    {
        return Entidad::where('entidad_principal', true)->firstOrFail();
    }

    public function test_descontar_oracion_reduce_el_stock(): void
    {
        $ent = $this->entidad();
        $oracion = Oracion::create(['titulo' => 'Refugio', 'tipo' => 'Librillo', 'precio' => 100]);
        InventarioEntidadOracion::create(['entidad_id' => $ent->id, 'oracion_id' => $oracion->id, 'cantidad' => 5]);

        $this->service()->descontarStock('oracion', $oracion->id, $ent->id, 2);

        $this->assertSame(3, (int) InventarioEntidadOracion::where('entidad_id', $ent->id)->where('oracion_id', $oracion->id)->value('cantidad'));
    }

    public function test_descontar_sin_stock_lanza_excepcion(): void
    {
        $ent = $this->entidad();
        $arte = Arte::create(['titulo' => 'Tarjeta', 'tipo' => 'Tarjeta A5', 'precio' => 50]);
        InventarioEntidadArte::create(['entidad_id' => $ent->id, 'arte_id' => $arte->id, 'cantidad' => 1]);

        $this->expectException(ValidationException::class);
        $this->service()->descontarStock('arte', $arte->id, $ent->id, 3);
    }

    public function test_devolver_arte_incrementa_el_stock(): void
    {
        $ent = $this->entidad();
        $arte = Arte::create(['titulo' => 'Tarjeta Dev', 'tipo' => 'Tarjeta A6', 'precio' => 50]);
        InventarioEntidadArte::create(['entidad_id' => $ent->id, 'arte_id' => $arte->id, 'cantidad' => 1]);

        $this->service()->devolverStock('arte', $arte->id, $ent->id, 2);

        $this->assertSame(3, (int) InventarioEntidadArte::where('entidad_id', $ent->id)->where('arte_id', $arte->id)->value('cantidad'));
    }

    public function test_descontar_libro_registra_historico(): void
    {
        $ent = $this->entidad();
        $libro = Libro::create(['titulo' => 'Libro Hist', 'tipo' => 'Físico', 'precio' => 200]);
        InventarioEntidadLibro::create(['entidad_id' => $ent->id, 'libro_id' => $libro->id, 'cantidad' => 4]);

        $historicoAntes = HistoricoPedidoLibro::where('libro_id', $libro->id)->count();

        $this->service()->descontarStock('libro', $libro->id, $ent->id, 1, ['email_comprador' => 'x@example.com']);

        $this->assertSame(3, (int) InventarioEntidadLibro::where('entidad_id', $ent->id)->where('libro_id', $libro->id)->value('cantidad'));
        $this->assertSame($historicoAntes + 1, HistoricoPedidoLibro::where('libro_id', $libro->id)->count());
    }
}

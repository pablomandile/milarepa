<?php

namespace Tests\Feature\Cobros;

use App\Models\Cobro;
use App\Models\InventarioEntidadLibro;
use App\Models\Libro;
use App\Models\MetodoPago;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Al registrar una venta de libros se genera un cobro cerrado (uno por venta)
 * en el ledger unificado, con el medio resuelto desde `modo`.
 */
class VentaGeneraCobroTest extends TestCase
{
    use DatabaseTransactions;

    public function test_store_de_venta_crea_un_cobro(): void
    {
        $entidadId = DB::table('entidades')->value('id');
        $libro = Libro::create(['titulo' => 'Libro Cobro Test', 'precio' => 1500]);
        InventarioEntidadLibro::create([
            'entidad_id' => $entidadId,
            'libro_id' => $libro->id,
            'cantidad' => 10,
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('inventario-libros.ventas.store'), [
            'fecha' => '2026-07-11',
            'entidad_id' => $entidadId,
            'libro_id' => $libro->id,
            'cantidad' => 2,
            'precio_unitario' => 1500,
            'montoTotal' => 3000,
            'modo' => 'Transferencia',
        ])->assertRedirect(route('inventario-libros.ventas.index'));

        $venta = Venta::where('libro_id', $libro->id)->firstOrFail();
        $cobros = $venta->cobros()->get();

        $this->assertCount(1, $cobros);
        $this->assertEquals(3000, (float) $cobros->first()->monto);
        $this->assertSame('venta', DB::table('cobros')->where('id', $cobros->first()->id)->value('cobrable_type'));
        $this->assertEquals(
            MetodoPago::where('nombre', 'Transferencia')->value('id'),
            $cobros->first()->metodo_pago_id
        );
        $this->assertSame('manual', $cobros->first()->origen);
    }
}

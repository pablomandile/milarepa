<?php

namespace Tests\Feature\Pos;

use App\Models\Actividad;
use App\Models\Comida;
use App\Models\Entidad;
use App\Models\EsquemaPrecio;
use App\Models\EsquemaPrecioMembresia;
use App\Models\Inscripcion;
use App\Models\Membresia;
use App\Models\MetodoPago;
use App\Models\Modalidad;
use App\Models\Moneda;
use App\Models\TipoActividad;
use App\Models\User;
use App\Models\VentaPos;
use App\Models\VentaPosItem;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * POS multi-moneda. Regla: lo único que puede venderse en otra moneda es una
 * actividad; el resto va siempre en pesos. Cada moneda se totaliza y se salda
 * por separado, con su propio medio de pago y sin conversión.
 */
class PosVentaMultiMonedaTest extends TestCase
{
    use DatabaseTransactions;

    private Moneda $principal;
    private Moneda $secundaria;
    private Comida $comidaConSecundaria;   // plana 1000, secundaria 10
    private Comida $comidaSoloPrincipal;   // plana 2000

    private function usuario(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }

    /** Actividad "sin membresía" a 10000 pesos / 100 en la moneda secundaria. */
    private function actividadDosMonedas(): Actividad
    {
        $this->principal = Moneda::principal()
            ?? Moneda::create(['nombre' => 'Pesos Test', 'simbolo' => '$', 'es_principal' => true]);
        $this->secundaria = Moneda::create(['nombre' => 'Dolar Test ' . uniqid(), 'simbolo' => 'U$T']);

        $general = Membresia::create(['nombre' => 'Sin membresía', 'valor' => 0]);
        $esquema = EsquemaPrecio::create(['nombre' => 'Esquema POS MM ' . uniqid()]);
        EsquemaPrecioMembresia::create([
            'esquema_precio_id' => $esquema->id,
            'membresia_id' => $general->id,
            'moneda_id' => $this->principal->id,
            'precio' => 10000,
        ]);
        EsquemaPrecioMembresia::create([
            'esquema_precio_id' => $esquema->id,
            'membresia_id' => $general->id,
            'moneda_id' => $this->secundaria->id,
            'precio' => 100,
        ]);

        $actividad = Actividad::create([
            'nombre' => 'Evento POS MultiMoneda ' . uniqid(),
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo PMM ' . uniqid()])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Presencial PMM ' . uniqid()])->id,
            'esquema_precio_id' => $esquema->id,
            'entidad_id' => DB::table('entidades')->value('id'),
            'fecha_inicio' => '2027-02-10 10:00:00',
            'fecha_fin' => '2027-02-11 20:00:00',
            'estado' => true,
        ]);

        $this->comidaConSecundaria = Comida::create(['nombre' => 'Comida PMM USD', 'descripcion' => 'x', 'precio' => 1000, 'vegano' => false, 'celiaco' => false]);
        $this->comidaConSecundaria->precios()->create(['moneda_id' => $this->secundaria->id, 'precio' => 10]);
        $this->comidaSoloPrincipal = Comida::create(['nombre' => 'Comida PMM Pesos', 'descripcion' => 'x', 'precio' => 2000, 'vegano' => false, 'celiaco' => false]);
        $actividad->comidas()->attach([$this->comidaConSecundaria->id, $this->comidaSoloPrincipal->id]);

        return $actividad;
    }

    private function metodo(string $nombre): MetodoPago
    {
        return MetodoPago::firstOrCreate(['nombre' => $nombre], ['descripcion' => $nombre, 'tipo_de_pago' => 'Presencial']);
    }

    public function test_venta_en_otra_moneda_separa_los_totales(): void
    {
        $actividad = $this->actividadDosMonedas();
        $entidad = Entidad::where('entidad_principal', true)->firstOrFail();
        $efectivo = $this->metodo('Efectivo');
        $transferencia = $this->metodo('Transferencia');

        $this->actingAs($this->usuario())->post(route('pos.store'), [
            'entidad_id' => $entidad->id,
            'metodo_pago_id' => $efectivo->id,
            'pagos' => [
                ['moneda_id' => $this->secundaria->id, 'metodo_pago_id' => $transferencia->id],
            ],
            'items' => [[
                'tipo' => 'inscripcion_actividad',
                'cantidad' => 1,
                'inscripcion' => [
                    'actividad_id' => $actividad->id,
                    'email' => 'pos.mm' . uniqid() . '@example.com',
                    'nombre' => 'Cliente PMM',
                    'registrar_datos' => true,
                    'moneda_id' => $this->secundaria->id,
                    // Una comida con precio en la moneda y otra sólo en pesos.
                    'comidas_ids' => [$this->comidaConSecundaria->id, $this->comidaSoloPrincipal->id],
                ],
            ]],
        ])->assertRedirect(route('pos.index'));

        $ins = Inscripcion::where('actividad_id', $actividad->id)->latest('id')->firstOrFail();
        $this->assertSame($this->secundaria->id, $ins->moneda_id);
        $this->assertEquals(110, (float) $ins->montoapagar);              // 100 actividad + 10 comida
        $this->assertEquals(2000, (float) $ins->monto_moneda_principal);  // comida sin precio en la moneda

        $venta = VentaPos::latest('id')->firstOrFail();
        // El total de la venta es SÓLO la porción en pesos; la otra moneda va aparte.
        $this->assertEquals(2000, (float) $venta->total);
        $this->assertSame([(string) $this->secundaria->id => 110.0], array_map('floatval', $venta->totales_por_moneda));

        $item = VentaPosItem::where('venta_pos_id', $venta->id)->firstOrFail();
        $this->assertSame($this->secundaria->id, $item->moneda_id);
        $this->assertEquals(110, (float) $item->subtotal);
        $this->assertEquals(2000, (float) $item->subtotal_moneda_principal);
    }

    public function test_cada_moneda_se_salda_con_su_propio_cobro(): void
    {
        $actividad = $this->actividadDosMonedas();
        $entidad = Entidad::where('entidad_principal', true)->firstOrFail();
        $efectivo = $this->metodo('Efectivo');
        $transferencia = $this->metodo('Transferencia');

        $this->actingAs($this->usuario())->post(route('pos.store'), [
            'entidad_id' => $entidad->id,
            'metodo_pago_id' => $efectivo->id,
            'pagos' => [
                ['moneda_id' => $this->secundaria->id, 'metodo_pago_id' => $transferencia->id],
            ],
            'items' => [[
                'tipo' => 'inscripcion_actividad',
                'cantidad' => 1,
                'inscripcion' => [
                    'actividad_id' => $actividad->id,
                    'email' => 'pos.mm2' . uniqid() . '@example.com',
                    'nombre' => 'Cliente PMM2',
                    'registrar_datos' => true,
                    'moneda_id' => $this->secundaria->id,
                    'comidas_ids' => [$this->comidaSoloPrincipal->id],
                ],
            ]],
        ])->assertRedirect(route('pos.index'));

        $ins = Inscripcion::where('actividad_id', $actividad->id)->latest('id')->firstOrFail();
        $cobros = $ins->cobros()->orderBy('id')->get();

        // Dos cobros: uno por moneda, cada uno con su medio de pago.
        $this->assertCount(2, $cobros);
        $this->assertSame($this->secundaria->id, $cobros[0]->moneda_id);
        $this->assertSame($transferencia->id, $cobros[0]->metodo_pago_id);
        $this->assertEquals(100, (float) $cobros[0]->monto);

        $this->assertSame($this->principal->id, $cobros[1]->moneda_id);
        $this->assertSame($efectivo->id, $cobros[1]->metodo_pago_id);
        $this->assertEquals(2000, (float) $cobros[1]->monto);

        // Con las dos porciones cobradas la inscripción queda saldada.
        $this->assertSame('Saldado', $ins->fresh()->pago);
    }

    public function test_venta_solo_en_pesos_no_cambia(): void
    {
        $actividad = $this->actividadDosMonedas();
        $entidad = Entidad::where('entidad_principal', true)->firstOrFail();
        $efectivo = $this->metodo('Efectivo');

        $this->actingAs($this->usuario())->post(route('pos.store'), [
            'entidad_id' => $entidad->id,
            'metodo_pago_id' => $efectivo->id,
            'items' => [[
                'tipo' => 'inscripcion_actividad',
                'cantidad' => 1,
                'inscripcion' => [
                    'actividad_id' => $actividad->id,
                    'email' => 'pos.pesos' . uniqid() . '@example.com',
                    'nombre' => 'Cliente Pesos',
                    'registrar_datos' => true,
                ],
            ]],
        ])->assertRedirect(route('pos.index'));

        $venta = VentaPos::latest('id')->firstOrFail();
        $this->assertEquals(10000, (float) $venta->total);
        $this->assertNull($venta->totales_por_moneda);
        $this->assertNull($venta->pagos_por_moneda);

        $item = VentaPosItem::where('venta_pos_id', $venta->id)->firstOrFail();
        $this->assertSame($this->principal->id, $item->moneda_id);
        $this->assertNull($item->subtotal_moneda_principal);
    }

    public function test_datos_actividad_devuelve_precios_en_la_moneda_pedida(): void
    {
        $actividad = $this->actividadDosMonedas();

        $respuesta = $this->actingAs($this->usuario())
            ->getJson(route('pos.actividad-datos', $actividad->id) . '?moneda_id=' . $this->secundaria->id)
            ->assertOk();

        $comidas = collect($respuesta->json('actividad.comidas'));
        $conPrecio = $comidas->firstWhere('id', $this->comidaConSecundaria->id);
        $sinPrecio = $comidas->firstWhere('id', $this->comidaSoloPrincipal->id);

        $this->assertEquals(10, $conPrecio['precio']);
        $this->assertFalse($conPrecio['en_principal']);

        // Sin precio en la moneda: se ofrece en pesos y marcado para cobrarse aparte.
        $this->assertEquals(2000, $sinPrecio['precio']);
        $this->assertTrue($sinPrecio['en_principal']);

        $monedas = collect($respuesta->json('actividad.monedas'));
        $this->assertCount(2, $monedas);
        $this->assertTrue($monedas->first()['es_principal']);
    }
}

<?php

namespace Tests\Feature\Servicios;

use App\Models\Comida;
use App\Models\Grabacion;
use App\Models\Moneda;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Trait TienePreciosPorMoneda: la columna plana del servicio es el precio en la
 * moneda principal; servicio_precios guarda las demás monedas. precioEnMoneda()
 * devuelve null si el servicio no tiene precio en la moneda pedida (el caller lo
 * cobra en la principal: total dividido).
 */
class ServicioPrecioTraitTest extends TestCase
{
    use DatabaseTransactions;

    private function monedaPrincipal(): Moneda
    {
        return Moneda::principal()
            ?? Moneda::create(['nombre' => 'Pesos Test', 'simbolo' => '$', 'es_principal' => true]);
    }

    private function monedaSecundaria(): Moneda
    {
        return Moneda::create(['nombre' => 'Dolar Test ' . uniqid(), 'simbolo' => 'U$T']);
    }

    public function test_precio_en_moneda_principal_o_null_usa_columna_plana(): void
    {
        $principal = $this->monedaPrincipal();
        $comida = Comida::create(['nombre' => 'Comida Trait', 'descripcion' => 'x', 'precio' => 1500, 'vegano' => false, 'celiaco' => false]);
        $grabacion = Grabacion::create(['nombre' => 'Grabacion Trait', 'valor' => 2000]);

        $this->assertSame(1500.0, $comida->precioEnMoneda(null));
        $this->assertSame(1500.0, $comida->precioEnMoneda($principal->id));
        // Grabación usa la columna plana `valor` (override de campoPrecioPlano).
        $this->assertSame(2000.0, $grabacion->precioEnMoneda(null));
        $this->assertSame(2000.0, $grabacion->precioEnMoneda($principal->id));
    }

    public function test_precio_en_moneda_secundaria_usa_fila_de_servicio_precios(): void
    {
        $this->monedaPrincipal();
        $secundaria = $this->monedaSecundaria();
        $comida = Comida::create(['nombre' => 'Comida Trait USD', 'descripcion' => 'x', 'precio' => 1500, 'vegano' => false, 'celiaco' => false]);
        $comida->precios()->create(['moneda_id' => $secundaria->id, 'precio' => 12.5]);

        $this->assertSame(12.5, $comida->precioEnMoneda($secundaria->id));
        // Sin fila en esa moneda → null (se cobrará en la principal).
        $otra = $this->monedaSecundaria();
        $this->assertNull($comida->precioEnMoneda($otra->id));
    }

    public function test_precios_por_moneda_arranca_con_la_fila_sintetica_principal(): void
    {
        $principal = $this->monedaPrincipal();
        $secundaria = $this->monedaSecundaria();
        $comida = Comida::create(['nombre' => 'Comida Accessor', 'descripcion' => 'x', 'precio' => 1500, 'vegano' => false, 'celiaco' => false]);
        $comida->precios()->create(['moneda_id' => $secundaria->id, 'precio' => 10]);
        // Fila espuria con la moneda principal: debe ignorarse (la principal
        // siempre sale de la columna plana).
        $comida->precios()->create(['moneda_id' => $principal->id, 'precio' => 999]);

        $filas = $comida->fresh()->precios_por_moneda;

        $this->assertTrue($filas[0]['es_principal']);
        $this->assertSame((int) $principal->id, $filas[0]['moneda_id']);
        $this->assertSame(1500.0, $filas[0]['precio']);

        $secundarias = array_values(array_filter($filas, fn ($f) => !$f['es_principal']));
        $this->assertCount(1, $secundarias);
        $this->assertSame((int) $secundaria->id, $secundarias[0]['moneda_id']);
        $this->assertSame(10.0, $secundarias[0]['precio']);
        $this->assertSame($secundaria->simbolo, $secundarias[0]['moneda']['simbolo']);
    }
}

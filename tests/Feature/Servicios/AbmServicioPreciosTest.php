<?php

namespace Tests\Feature\Servicios;

use App\Models\Comida;
use App\Models\Moneda;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * ABMs de servicios con precios por moneda: el array `precios` del form se
 * sincroniza contra servicio_precios (crea/actualiza/borra), las filas con la
 * moneda principal se descartan (ese precio vive en la columna plana) y la
 * validación rechaza monedas repetidas.
 */
class AbmServicioPreciosTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function monedaPrincipal(): Moneda
    {
        return Moneda::principal()
            ?? Moneda::create(['nombre' => 'Pesos Test', 'simbolo' => '$', 'es_principal' => true]);
    }

    private function monedaSecundaria(): Moneda
    {
        return Moneda::create(['nombre' => 'Dolar Test ' . uniqid(), 'simbolo' => 'U$T']);
    }

    public function test_store_de_comida_con_precios_crea_filas_hijas(): void
    {
        $this->monedaPrincipal();
        $secundaria = $this->monedaSecundaria();

        $this->actingAs($this->admin())->post(route('comidas.store'), [
            'nombre' => 'Comida ABM Multi',
            'descripcion' => 'Con precio en otra moneda',
            'precio' => 1800,
            'vegano' => false,
            'celiaco' => false,
            'precios' => [
                ['moneda_id' => $secundaria->id, 'precio' => 15, 'botonpago_id' => null],
            ],
        ])->assertRedirect(route('comidas.index'));

        $comida = Comida::where('nombre', 'Comida ABM Multi')->firstOrFail();
        $this->assertDatabaseHas('servicio_precios', [
            'servicioable_type' => 'comida',
            'servicioable_id' => $comida->id,
            'moneda_id' => $secundaria->id,
            'precio' => 15,
        ]);
    }

    public function test_update_sincroniza_borra_y_actualiza_filas(): void
    {
        $this->monedaPrincipal();
        $secundariaA = $this->monedaSecundaria();
        $secundariaB = $this->monedaSecundaria();

        $comida = Comida::create(['nombre' => 'Comida Sync', 'descripcion' => 'x', 'precio' => 1000, 'vegano' => false, 'celiaco' => false]);
        $comida->precios()->create(['moneda_id' => $secundariaA->id, 'precio' => 10]);
        $comida->precios()->create(['moneda_id' => $secundariaB->id, 'precio' => 20]);

        // Queda solo A con precio nuevo: B debe borrarse.
        $this->actingAs($this->admin())->put(route('comidas.update', $comida->id), [
            'nombre' => 'Comida Sync',
            'descripcion' => 'x',
            'precio' => 1000,
            'precios' => [
                ['moneda_id' => $secundariaA->id, 'precio' => 11],
            ],
        ])->assertRedirect(route('comidas.index'));

        $this->assertDatabaseHas('servicio_precios', ['servicioable_type' => 'comida', 'servicioable_id' => $comida->id, 'moneda_id' => $secundariaA->id, 'precio' => 11]);
        $this->assertDatabaseMissing('servicio_precios', ['servicioable_type' => 'comida', 'servicioable_id' => $comida->id, 'moneda_id' => $secundariaB->id]);
    }

    public function test_fila_con_moneda_principal_se_descarta(): void
    {
        $principal = $this->monedaPrincipal();

        $this->actingAs($this->admin())->post(route('comidas.store'), [
            'nombre' => 'Comida Principal Espuria',
            'descripcion' => 'x',
            'precio' => 500,
            'vegano' => false,
            'celiaco' => false,
            'precios' => [
                ['moneda_id' => $principal->id, 'precio' => 999],
            ],
        ])->assertRedirect(route('comidas.index'));

        $comida = Comida::where('nombre', 'Comida Principal Espuria')->firstOrFail();
        $this->assertDatabaseMissing('servicio_precios', [
            'servicioable_type' => 'comida',
            'servicioable_id' => $comida->id,
        ]);
    }

    public function test_moneda_repetida_en_el_payload_falla_validacion(): void
    {
        $this->monedaPrincipal();
        $secundaria = $this->monedaSecundaria();

        $this->actingAs($this->admin())->postJson(route('comidas.store'), [
            'nombre' => 'Comida Duplicada',
            'descripcion' => 'x',
            'precio' => 500,
            'precios' => [
                ['moneda_id' => $secundaria->id, 'precio' => 10],
                ['moneda_id' => $secundaria->id, 'precio' => 20],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors(['precios.0.moneda_id']);
    }
}

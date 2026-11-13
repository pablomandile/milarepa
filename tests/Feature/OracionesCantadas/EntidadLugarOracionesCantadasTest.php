<?php

namespace Tests\Feature\OracionesCantadas;

use App\Models\Entidad;
use App\Models\Lugar;
use App\Models\OracionCantada;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Entidad y Lugar de las oraciones cantadas: se cargan en el formulario (la
 * entidad es obligatoria, el lugar no) y la página pública muestra la entidad y
 * el lugar propio o, si no tiene, la dirección de la entidad.
 */
class EntidadLugarOracionesCantadasTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function entidad(): Entidad
    {
        return Entidad::create(['nombre' => 'Sede Oracion ' . uniqid(), 'direccion' => 'Calle Sede 100', 'entidad_principal' => false]);
    }

    private function lugar(): Lugar
    {
        return Lugar::create([
            'nombre' => 'Templo ' . uniqid(),
            'descripcion' => 'Lugar de prueba',
            'direccion' => 'Av. Libertador 2000',
            'telefono' => '1100000000',
        ]);
    }

    private function oracion(array $attrs = []): OracionCantada
    {
        return OracionCantada::create(array_merge([
            'nombre' => 'Oración lugar ' . uniqid(),
            'descripcion' => 'Descripción',
            'periodicidad' => 'Diaria',
            'dias_semana' => ['martes'],
            'hora' => '19:00',
            'mostrar_en_calendario' => true,
        ], $attrs));
    }

    private function payload(OracionCantada $oracion, array $extra): array
    {
        return array_merge([
            'nombre' => $oracion->nombre,
            'descripcion' => $oracion->descripcion,
            'periodicidad' => 'Diaria',
            'dias_semana' => ['martes'],
            'hora' => '19:00',
            'mostrar_en_calendario' => true,
        ], $extra);
    }

    private function itemPublico(OracionCantada $oracion): array
    {
        $props = $this->get('/oraciones-cantadas-publicas')->assertOk()->viewData('page')['props'];
        $item = collect($props['oraciones'])->firstWhere('id', $oracion->id);
        $this->assertNotNull($item, 'La oración no viajó a la página pública');

        return $item;
    }

    public function test_guarda_entidad_y_lugar_desde_el_formulario(): void
    {
        $oracion = $this->oracion();
        $entidad = $this->entidad();
        $lugar = $this->lugar();

        $this->actingAs($this->admin())
            ->put(route('oracionescantadas.update', $oracion->id), $this->payload($oracion, [
                'entidad_id' => $entidad->id,
                'lugar_id' => $lugar->id,
            ]))
            ->assertSessionHasNoErrors();

        $oracion->refresh();
        $this->assertSame($entidad->id, $oracion->entidad_id);
        $this->assertSame($lugar->id, $oracion->lugar_id);
    }

    public function test_la_entidad_es_obligatoria_y_el_lugar_no(): void
    {
        $oracion = $this->oracion();

        $this->actingAs($this->admin())
            ->put(route('oracionescantadas.update', $oracion->id), $this->payload($oracion, ['entidad_id' => null]))
            ->assertSessionHasErrors('entidad_id');

        $this->actingAs($this->admin())
            ->put(route('oracionescantadas.update', $oracion->id), $this->payload($oracion, [
                'entidad_id' => $this->entidad()->id,
                'lugar_id' => null,
            ]))
            ->assertSessionHasNoErrors();
    }

    public function test_pagina_publica_muestra_entidad_y_lugar_propio(): void
    {
        $entidad = $this->entidad();
        $lugar = $this->lugar();
        $item = $this->itemPublico($this->oracion(['entidad_id' => $entidad->id, 'lugar_id' => $lugar->id]));

        $this->assertSame($entidad->nombre, $item['entidad']);
        $this->assertSame(['nombre' => $lugar->nombre, 'direccion' => 'Av. Libertador 2000'], $item['lugar']);
    }

    public function test_pagina_publica_sin_lugar_usa_la_direccion_de_la_entidad(): void
    {
        $entidad = $this->entidad();
        $item = $this->itemPublico($this->oracion(['entidad_id' => $entidad->id]));

        $this->assertSame($entidad->nombre, $item['entidad']);
        $this->assertSame(['nombre' => null, 'direccion' => 'Calle Sede 100'], $item['lugar']);
    }
}

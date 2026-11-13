<?php

namespace Tests\Feature\OracionesCantadas;

use App\Models\OracionCantada;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * La página pública de Oraciones cantadas manda la descripción de cada oración,
 * que se muestra en el reverso de la imagen (efecto flip).
 */
class PaginaPublicaDescripcionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_la_pagina_publica_incluye_la_descripcion(): void
    {
        $oracion = OracionCantada::create([
            'nombre' => 'Oración flip ' . uniqid(),
            'descripcion' => "Primer párrafo.\n\nSegundo **destacado**.",
            'periodicidad' => 'Diaria',
            'dias_semana' => ['martes'],
            'hora' => '19:00',
            'mostrar_en_calendario' => true,
        ]);

        $props = $this->get('/oraciones-cantadas-publicas')->assertOk()->viewData('page')['props'];
        $item = collect($props['oraciones'])->firstWhere('id', $oracion->id);

        $this->assertNotNull($item, 'La oración no viajó a la página pública');
        $this->assertSame("Primer párrafo.\n\nSegundo **destacado**.", $item['descripcion']);
    }
}

<?php

namespace Tests\Feature\Clases;

use App\Models\Clase;
use App\Models\Entidad;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * La página pública de Clases manda la descripción de cada clase, que se
 * muestra en el reverso de la imagen (efecto flip).
 */
class DescripcionPaginaPublicaClasesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_la_pagina_publica_incluye_la_descripcion(): void
    {
        $entidad = Entidad::create(['nombre' => 'Sede Descripcion Test', 'entidad_principal' => false]);
        $clase = Clase::create([
            'nombre' => 'Clase flip ' . uniqid(),
            'descripcion' => "Primer párrafo.\n\nSegundo **destacado**.",
            'entidad_id' => $entidad->id,
            'mes_referencia' => now()->format('Y-m'),
            'dias_semana' => ['lunes'],
            'horario_desde' => '19:00',
            'horario_hasta' => '20:30',
            'activa' => true,
        ]);

        $props = $this->get('/clases-publicas')->assertOk()->viewData('page')['props'];
        $item = collect($props['clases'])->firstWhere('id', $clase->id);

        $this->assertNotNull($item, 'La clase no viajó a la página pública');
        $this->assertSame("Primer párrafo.\n\nSegundo **destacado**.", $item['descripcion']);
    }
}

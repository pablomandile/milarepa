<?php

namespace Tests\Feature\Clases;

use App\Models\Clase;
use App\Models\Entidad;
use App\Models\Lugar;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * La página pública de Clases muestra el lugar de cada clase: el lugar propio si
 * la clase lo especifica, y si no, la dirección de su entidad.
 */
class LugarPaginaPublicaClasesTest extends TestCase
{
    use DatabaseTransactions;

    private function clase(Entidad $entidad, ?Lugar $lugar = null): Clase
    {
        return Clase::create([
            'nombre' => 'Clase lugar ' . uniqid(),
            'entidad_id' => $entidad->id,
            'lugar_id' => $lugar?->id,
            'mes_referencia' => now()->format('Y-m'),
            'dias_semana' => ['lunes'],
            'horario_desde' => '19:00',
            'horario_hasta' => '20:30',
            'activa' => true,
        ]);
    }

    private function lugarDeClase(Clase $clase): ?array
    {
        $props = $this->get('/clases-publicas')->assertOk()->viewData('page')['props'];

        $item = collect($props['clases'])->firstWhere('id', $clase->id);
        $this->assertNotNull($item, 'La clase no viajó a la página pública');

        return $item['lugar'];
    }

    public function test_usa_el_lugar_de_la_clase_si_lo_especifica(): void
    {
        $entidad = Entidad::create(['nombre' => 'Sede Lugar Test', 'direccion' => 'Calle Sede 100', 'entidad_principal' => false]);
        $lugar = Lugar::create(['nombre' => 'Biblioteca Popular ' . uniqid(), 'descripcion' => 'Lugar de prueba', 'direccion' => 'Av. Libertador 2000', 'telefono' => '1100000000']);

        $this->assertSame(
            ['nombre' => $lugar->nombre, 'direccion' => 'Av. Libertador 2000'],
            $this->lugarDeClase($this->clase($entidad, $lugar))
        );
    }

    public function test_sin_lugar_usa_la_direccion_de_la_entidad(): void
    {
        $entidad = Entidad::create(['nombre' => 'Sede Lugar Test', 'direccion' => 'Calle Sede 100', 'entidad_principal' => false]);

        $this->assertSame(
            ['nombre' => null, 'direccion' => 'Calle Sede 100'],
            $this->lugarDeClase($this->clase($entidad))
        );
    }

    public function test_sin_lugar_ni_direccion_de_entidad_devuelve_null(): void
    {
        $entidad = Entidad::create(['nombre' => 'Sede Sin Direccion', 'entidad_principal' => false]);

        $this->assertNull($this->lugarDeClase($this->clase($entidad)));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Actividad;
use App\Models\Clase;
use App\Models\Entidad;
use App\Models\EsquemaPrecio;
use App\Models\Modalidad;
use App\Models\TipoActividad;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Filtro público por sede vía URL (?sede=<palabra>) en la grilla de actividades,
 * su versión embebible y la página de clases. La palabra se resuelve contra el
 * nombre de la entidad; si no coincide con exactamente una, se muestra todo.
 *
 * Las entidades de prueba usan "Zetaprueba" para no chocar con los nombres
 * reales de la base compartida de testing.
 */
class SedePorParametroTest extends TestCase
{
    use DatabaseTransactions;

    private function entidad(string $nombre): Entidad
    {
        return Entidad::create(['nombre' => $nombre, 'entidad_principal' => false]);
    }

    private function actividad(Entidad $entidad): Actividad
    {
        return Actividad::create([
            'nombre' => 'Actividad ' . $entidad->nombre,
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo ' . $entidad->id])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Modalidad ' . $entidad->id])->id,
            'esquema_precio_id' => EsquemaPrecio::create(['nombre' => 'Esquema ' . $entidad->id])->id,
            'entidad_id' => $entidad->id,
            'fecha_inicio' => '2026-09-01 10:00:00',
            'fecha_fin' => '2026-09-02 20:00:00',
            'estado' => true,
        ]);
    }

    private function clase(Entidad $entidad): Clase
    {
        return Clase::create([
            'nombre' => 'Clase ' . $entidad->nombre,
            'entidad_id' => $entidad->id,
            'mes_referencia' => now()->format('Y-m'),
            'dias_semana' => ['lunes'],
            'horario_desde' => '19:00',
            'horario_hasta' => '20:30',
            'activa' => true,
        ]);
    }

    /** IDs que la página Inertia mandó en una prop de tipo lista. */
    private function idsDeProp(TestResponse $response, string $prop): array
    {
        return collect($response->viewData('page')['props'][$prop])
            ->pluck('id')
            ->all();
    }

    // --- Resolución de la palabra -----------------------------------------

    public function test_resuelve_la_entidad_por_una_palabra_de_su_nombre(): void
    {
        $entidad = $this->entidad('Anexo Zetaprueba Kuxira');

        $this->assertSame($entidad->id, Entidad::resolverPorPalabra('kuxira')?->id);
    }

    public function test_ignora_mayusculas_acentos_y_separadores(): void
    {
        $entidad = $this->entidad('Anexo Zetaprueba Kúrix Vandel');

        $this->assertSame($entidad->id, Entidad::resolverPorPalabra('KURIX')?->id);
        $this->assertSame($entidad->id, Entidad::resolverPorPalabra('kúrix')?->id);
        $this->assertSame($entidad->id, Entidad::resolverPorPalabra('kurix-vandel')?->id);
        $this->assertSame($entidad->id, Entidad::resolverPorPalabra('Kurix Vandel')?->id);
    }

    public function test_palabra_ambigua_o_desconocida_no_resuelve_ninguna_entidad(): void
    {
        $this->entidad('Anexo Zetaprueba Norte');
        $this->entidad('Anexo Zetaprueba Sur');

        // "zetaprueba" está en las dos: sin ganadora, la página muestra todo.
        $this->assertNull(Entidad::resolverPorPalabra('zetaprueba'));
        $this->assertNull(Entidad::resolverPorPalabra('sedequenoexiste'));
        $this->assertNull(Entidad::resolverPorPalabra(''));
        $this->assertNull(Entidad::resolverPorPalabra('   '));
        $this->assertNull(Entidad::resolverPorPalabra(null));
    }

    // --- Grilla de actividades --------------------------------------------

    public function test_grilla_muestra_solo_las_actividades_de_la_sede_pedida(): void
    {
        $norte = $this->entidad('Anexo Zetaprueba Norte');
        $sur = $this->entidad('Anexo Zetaprueba Sur');
        $actividadNorte = $this->actividad($norte);
        $actividadSur = $this->actividad($sur);

        $ids = $this->idsDeProp($this->get('/grid-actividades?sede=norte'), 'actividades');

        $this->assertContains($actividadNorte->id, $ids);
        $this->assertNotContains($actividadSur->id, $ids);
    }

    public function test_grilla_muestra_todo_si_la_sede_es_ambigua_o_no_existe(): void
    {
        $norte = $this->entidad('Anexo Zetaprueba Norte');
        $sur = $this->entidad('Anexo Zetaprueba Sur');
        $actividadNorte = $this->actividad($norte);
        $actividadSur = $this->actividad($sur);

        foreach (['?sede=zetaprueba', '?sede=sedequenoexiste', ''] as $query) {
            $ids = $this->idsDeProp($this->get('/grid-actividades' . $query), 'actividades');

            $this->assertContains($actividadNorte->id, $ids, "Falló con '{$query}'");
            $this->assertContains($actividadSur->id, $ids, "Falló con '{$query}'");
        }
    }

    public function test_grilla_embebida_tambien_filtra_por_sede(): void
    {
        $norte = $this->entidad('Anexo Zetaprueba Norte');
        $sur = $this->entidad('Anexo Zetaprueba Sur');
        $actividadNorte = $this->actividad($norte);
        $actividadSur = $this->actividad($sur);

        $ids = $this->idsDeProp($this->get('/grillaembebida?sede=norte'), 'actividades');
        $this->assertContains($actividadNorte->id, $ids);
        $this->assertNotContains($actividadSur->id, $ids);

        // Sin parámetro sigue mostrando todas (el embed actual no cambia).
        $idsSinFiltro = $this->idsDeProp($this->get('/grillaembebida'), 'actividades');
        $this->assertContains($actividadNorte->id, $idsSinFiltro);
        $this->assertContains($actividadSur->id, $idsSinFiltro);
    }

    // --- Clases públicas ---------------------------------------------------

    public function test_clases_preselecciona_la_sede_sin_recortar_el_listado(): void
    {
        $norte = $this->entidad('Anexo Zetaprueba Norte');
        $sur = $this->entidad('Anexo Zetaprueba Sur');
        $claseNorte = $this->clase($norte);
        $claseSur = $this->clase($sur);

        $response = $this->get('/clases-publicas?sede=norte');
        $props = $response->viewData('page')['props'];

        $this->assertSame($norte->id, $props['entidadSeleccionadaId']);
        // El filtrado es client-side: viajan todas las clases para que los
        // demás botones de sede sigan funcionando.
        $ids = $this->idsDeProp($response, 'clases');
        $this->assertContains($claseNorte->id, $ids);
        $this->assertContains($claseSur->id, $ids);
    }

    public function test_clases_no_preselecciona_nada_si_la_sede_es_ambigua_o_falta(): void
    {
        $this->entidad('Anexo Zetaprueba Norte');
        $this->entidad('Anexo Zetaprueba Sur');

        foreach (['?sede=zetaprueba', '?sede=sedequenoexiste', ''] as $query) {
            $props = $this->get('/clases-publicas' . $query)->viewData('page')['props'];

            $this->assertNull($props['entidadSeleccionadaId'], "Falló con '{$query}'");
        }
    }
}

<?php

namespace Tests\Feature\Actividades;

use App\Models\Actividad;
use App\Models\Comida;
use App\Models\EsquemaPrecio;
use App\Models\Hospedaje;
use App\Models\Maestro;
use App\Models\Modalidad;
use App\Models\TipoActividad;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Clonar un curso/retiro copia su estructura (campos y pivotes) y deja la copia
 * no visible (estado=false), lista para editar.
 */
class ClonarActividadTest extends TestCase
{
    use DatabaseTransactions;

    private function actividad(string $nombre = 'Retiro Clonable'): Actividad
    {
        $actividad = Actividad::create([
            'nombre' => $nombre,
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo Clon'])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Presencial Clon'])->id,
            'esquema_precio_id' => EsquemaPrecio::create(['nombre' => 'Esquema Clon'])->id,
            'entidad_id' => DB::table('entidades')->value('id'),
            'fecha_inicio' => '2026-12-01 10:00:00',
            'fecha_fin' => '2026-12-03 18:00:00',
            'link_web' => 'https://ejemplo.test/retiro',
            'estado' => true,
        ]);

        $actividad->maestros()->sync([Maestro::create(['nombre' => 'Maestro Clon'])->id]);
        $actividad->comidas()->sync([Comida::create(['nombre' => 'Almuerzo Clon', 'descripcion' => 'x', 'precio' => 1000, 'vegano' => false, 'celiaco' => false])->id]);
        $actividad->hospedajes()->sync([Hospedaje::create(['nombre' => 'Cuarto Clon', 'descripcion' => 'x', 'precio' => 5000])->id => ['cantidad' => 7]]);

        return $actividad;
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_clona_campos_y_pivotes_y_deja_la_copia_no_visible(): void
    {
        $original = $this->actividad();

        $response = $this->actingAs($this->admin())->post(route('actividades.clonar', $original->id));

        $clon = Actividad::where('nombre', 'Retiro Clonable (copia)')->latest('id')->first();
        $this->assertNotNull($clon, 'No se creó la copia');
        $response->assertRedirect(route('actividades.edit', $clon->id));

        $this->assertFalse($clon->estado, 'La copia debe nacer no visible');
        $this->assertTrue($original->fresh()->estado, 'El original no debe cambiar');
        $this->assertSame($original->tipo_actividad_id, $clon->tipo_actividad_id);
        $this->assertSame($original->entidad_id, $clon->entidad_id);
        $this->assertSame('https://ejemplo.test/retiro', $clon->link_web);
        $this->assertEquals($original->fecha_inicio, $clon->fecha_inicio);

        $this->assertEquals($original->maestros->pluck('id'), $clon->maestros->pluck('id'));
        $this->assertEquals($original->comidas->pluck('id'), $clon->comidas->pluck('id'));
        $this->assertSame(7, (int) $clon->hospedajes->first()->pivot->cantidad);
        $this->assertSame(0, $clon->inscripciones()->count());
    }

    public function test_el_nombre_de_la_copia_respeta_el_maximo_del_formulario(): void
    {
        $original = $this->actividad(str_repeat('a', 80));

        $this->actingAs($this->admin())->post(route('actividades.clonar', $original->id));

        $clon = Actividad::where('id', '>', $original->id)->latest('id')->first();
        $this->assertSame(80, mb_strlen($clon->nombre));
    }

    public function test_requiere_estar_logueado(): void
    {
        $original = $this->actividad();

        $this->post(route('actividades.clonar', $original->id))->assertRedirect(route('login'));
        $this->assertSame(1, Actividad::where('nombre', 'like', 'Retiro Clonable%')->count());
    }
}

<?php

namespace Tests\Feature\Actividades;

use App\Models\Actividad;
use App\Models\EsquemaPrecio;
use App\Models\Modalidad;
use App\Models\TipoActividad;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * La casilla "Promoción 2x1" del formulario de actividad se guarda, y si no
 * viaja (o viaja vacía) queda en false: la columna no admite null.
 */
class Promo2x1FormularioTest extends TestCase
{
    use DatabaseTransactions;

    private function payload(Actividad $actividad, array $extra = []): array
    {
        return array_merge([
            'tipo_actividad_id' => $actividad->tipo_actividad_id,
            'nombre' => $actividad->nombre,
            'fecha_inicio' => '2026-12-20 10:00:00',
            'fecha_fin' => '2026-12-20 13:00:00',
            'entidad_id' => $actividad->entidad_id,
            'modalidad_id' => $actividad->modalidad_id,
            'esquema_precio_id' => $actividad->esquema_precio_id,
            'estado' => true,
        ], $extra);
    }

    private function actividad(): Actividad
    {
        return Actividad::create([
            'nombre' => 'Curso Form 2x1',
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo Form 2x1'])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Presencial Form 2x1'])->id,
            'esquema_precio_id' => EsquemaPrecio::create(['nombre' => 'Esquema Form 2x1'])->id,
            'entidad_id' => DB::table('entidades')->value('id'),
            'fecha_inicio' => '2026-12-20 10:00:00',
            'fecha_fin' => '2026-12-20 13:00:00',
            'estado' => true,
        ]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_activar_y_desactivar_la_promo_desde_el_formulario(): void
    {
        $actividad = $this->actividad();
        $this->assertFalse($actividad->fresh()->promo_2x1);

        // FormData de Inertia manda los booleanos como "1"/"0".
        $this->actingAs($this->admin())
            ->put(route('actividades.update', $actividad->id), $this->payload($actividad, ['promo_2x1' => '1']))
            ->assertSessionHasNoErrors();
        $this->assertTrue($actividad->fresh()->promo_2x1);

        $this->actingAs($this->admin())
            ->put(route('actividades.update', $actividad->id), $this->payload($actividad, ['promo_2x1' => '0']))
            ->assertSessionHasNoErrors();
        $this->assertFalse($actividad->fresh()->promo_2x1);
    }

    public function test_sin_el_campo_queda_en_false(): void
    {
        $actividad = $this->actividad();
        $actividad->update(['promo_2x1' => true]);

        $this->actingAs($this->admin())
            ->put(route('actividades.update', $actividad->id), $this->payload($actividad))
            ->assertSessionHasNoErrors();

        $this->assertFalse($actividad->fresh()->promo_2x1);
    }
}

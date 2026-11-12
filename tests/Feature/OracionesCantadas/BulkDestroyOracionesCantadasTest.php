<?php

namespace Tests\Feature\OracionesCantadas;

use App\Models\OracionCantada;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Borrado en lote desde la gestión de Oraciones cantadas ("Seleccionar" + "Borrar").
 */
class BulkDestroyOracionesCantadasTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function oracion(string $nombre): OracionCantada
    {
        return OracionCantada::create([
            'nombre' => $nombre,
            'descripcion' => 'Descripción de prueba',
            'periodicidad' => 'Mensual',
            'dia' => 10,
            'hora' => '19:00',
        ]);
    }

    public function test_borra_solo_las_oraciones_seleccionadas(): void
    {
        $a = $this->oracion('Oración A');
        $b = $this->oracion('Oración B');
        $c = $this->oracion('Oración C');

        $this->actingAs($this->admin())
            ->from(route('oracionescantadas.index'))
            ->post(route('oracionescantadas.bulkDestroy'), ['ids' => [$a->id, $b->id]])
            ->assertRedirect(route('oracionescantadas.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('oraciones_cantadas', ['id' => $a->id]);
        $this->assertDatabaseMissing('oraciones_cantadas', ['id' => $b->id]);
        $this->assertDatabaseHas('oraciones_cantadas', ['id' => $c->id]);
    }

    public function test_rechaza_lista_vacia_o_ids_inexistentes(): void
    {
        $a = $this->oracion('Oración A');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('oracionescantadas.bulkDestroy'), ['ids' => []])
            ->assertSessionHasErrors('ids');

        $this->actingAs($admin)
            ->post(route('oracionescantadas.bulkDestroy'), ['ids' => [$a->id, 999999999]])
            ->assertSessionHasErrors('ids.1');

        $this->assertDatabaseHas('oraciones_cantadas', ['id' => $a->id]);
    }

    public function test_invitado_no_puede_borrar(): void
    {
        $a = $this->oracion('Oración A');

        $this->post(route('oracionescantadas.bulkDestroy'), ['ids' => [$a->id]])
            ->assertRedirect(route('login'));

        $this->assertDatabaseHas('oraciones_cantadas', ['id' => $a->id]);
    }
}

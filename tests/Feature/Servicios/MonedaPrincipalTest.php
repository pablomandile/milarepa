<?php

namespace Tests\Feature\Servicios;

use App\Models\Moneda;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * ABM de Monedas: siempre debe existir exactamente una moneda principal.
 */
class MonedaPrincipalTest extends TestCase
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

    public function test_marcar_principal_desmarca_la_anterior(): void
    {
        $anterior = $this->monedaPrincipal();

        $this->actingAs($this->admin())->post(route('monedas.store'), [
            'nombre' => 'Nueva Principal',
            'simbolo' => 'NP',
            'es_principal' => true,
        ])->assertRedirect(route('monedas.index'));

        $this->assertFalse((bool) $anterior->fresh()->es_principal);
        $nueva = Moneda::where('nombre', 'Nueva Principal')->firstOrFail();
        $this->assertTrue((bool) $nueva->es_principal);
        $this->assertSame(1, Moneda::where('es_principal', true)->count());
    }

    public function test_no_se_puede_desmarcar_la_unica_principal(): void
    {
        $principal = $this->monedaPrincipal();

        $this->actingAs($this->admin())
            ->from(route('monedas.index'))
            ->put(route('monedas.update', $principal->id), [
                'nombre' => $principal->nombre,
                'simbolo' => $principal->simbolo,
                'es_principal' => false,
            ])->assertSessionHasErrors('es_principal');

        $this->assertTrue((bool) $principal->fresh()->es_principal);
    }

    public function test_no_se_puede_eliminar_la_moneda_principal(): void
    {
        $principal = $this->monedaPrincipal();

        $this->actingAs($this->admin())
            ->delete(route('monedas.destroy', $principal->id))
            ->assertRedirect(route('monedas.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('monedas', ['id' => $principal->id, 'deleted_at' => null]);
    }
}

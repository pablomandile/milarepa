<?php

namespace Tests\Feature\Tharpa;

use App\Models\Entidad;
use App\Models\Otro;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Catálogo e inventario de "Otros" (cajón de sastre de la tienda Tharpa).
 * El `tipo` es texto libre opcional.
 */
class OtrosTest extends TestCase
{
    use DatabaseTransactions;

    private function usuario(): User
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);

        return User::factory()->create();
    }

    public function test_crear_otro_persiste_con_tipo_libre(): void
    {
        $resp = $this->actingAs($this->usuario())->post(route('otros.store'), [
            'titulo' => 'Mala de sándalo',
            'tipo' => 'Mala',
            'precio' => 6000,
        ]);

        $resp->assertRedirect(route('otros.index'));
        $this->assertDatabaseHas('otros', ['titulo' => 'Mala de sándalo', 'tipo' => 'Mala']);
    }

    public function test_crear_otro_sin_tipo_es_valido(): void
    {
        $resp = $this->actingAs($this->usuario())->post(route('otros.store'), [
            'titulo' => 'Artículo sin tipo',
            'precio' => 100,
        ]);

        $resp->assertRedirect(route('otros.index'));
        $this->assertDatabaseHas('otros', ['titulo' => 'Artículo sin tipo', 'tipo' => null]);
    }

    public function test_inventario_otro_crea_stock_en_la_entidad_principal(): void
    {
        $principal = Entidad::where('entidad_principal', true)->first();
        $this->assertNotNull($principal, 'Se requiere una entidad principal en la base de testing.');

        $otro = Otro::create(['titulo' => 'Artículo Test Stock', 'tipo' => 'Incienso', 'precio' => 500]);

        $resp = $this->actingAs($this->usuario())->post(route('inventario-otros.store'), [
            'otro_id' => $otro->id,
            'cantidad' => 9,
        ]);

        $resp->assertRedirect(route('inventario-otros.index'));
        $this->assertDatabaseHas('inventario_entidad_otro', [
            'entidad_id' => $principal->id,
            'otro_id' => $otro->id,
            'cantidad' => 9,
        ]);
    }
}

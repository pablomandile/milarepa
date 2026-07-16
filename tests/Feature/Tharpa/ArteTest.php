<?php

namespace Tests\Feature\Tharpa;

use App\Models\Arte;
use App\Models\Entidad;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Catálogo e inventario de Arte (categoría de la tienda Tharpa).
 */
class ArteTest extends TestCase
{
    use DatabaseTransactions;

    private function usuario(): User
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);

        return User::factory()->create();
    }

    public function test_crear_arte_persiste_con_tipo(): void
    {
        $resp = $this->actingAs($this->usuario())->post(route('arte.store'), [
            'titulo' => 'Tarjeta Buda de la Medicina',
            'tipo' => 'Tarjeta A5',
            'precio' => 1500,
        ]);

        $resp->assertRedirect(route('arte.index'));
        $this->assertDatabaseHas('arte', ['titulo' => 'Tarjeta Buda de la Medicina', 'tipo' => 'Tarjeta A5']);
    }

    public function test_tipo_invalido_es_rechazado(): void
    {
        $resp = $this->actingAs($this->usuario())->post(route('arte.store'), [
            'titulo' => 'Formato inexistente',
            'tipo' => 'Ebook', // válido para libros, NO para arte
            'precio' => 100,
        ]);

        $resp->assertSessionHasErrors('tipo');
    }

    public function test_inventario_arte_crea_stock_en_la_entidad_principal(): void
    {
        $principal = Entidad::where('entidad_principal', true)->first();
        $this->assertNotNull($principal, 'Se requiere una entidad principal en la base de testing.');

        $arte = Arte::create(['titulo' => 'Tarjeta Test Stock', 'tipo' => 'Tarjeta Cuadrada', 'precio' => 800]);

        $resp = $this->actingAs($this->usuario())->post(route('inventario-arte.store'), [
            'arte_id' => $arte->id,
            'cantidad' => 12,
        ]);

        $resp->assertRedirect(route('inventario-arte.index'));
        $this->assertDatabaseHas('inventario_entidad_arte', [
            'entidad_id' => $principal->id,
            'arte_id' => $arte->id,
            'cantidad' => 12,
        ]);
    }
}

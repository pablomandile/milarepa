<?php

namespace Tests\Feature\Tharpa;

use App\Models\Entidad;
use App\Models\Oracion;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Catálogo e inventario de Oraciones (categoría de la tienda Tharpa).
 */
class OracionesTest extends TestCase
{
    use DatabaseTransactions;

    private function usuario(): User
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);

        return User::factory()->create();
    }

    public function test_crear_oracion_persiste_con_tipo(): void
    {
        $resp = $this->actingAs($this->usuario())->post(route('oraciones.store'), [
            'titulo' => 'Oración del Refugio',
            'tipo' => 'Librillo',
            'precio' => 3000,
        ]);

        $resp->assertRedirect(route('oraciones.index'));
        $this->assertDatabaseHas('oraciones', ['titulo' => 'Oración del Refugio', 'tipo' => 'Librillo']);
    }

    public function test_tipo_invalido_es_rechazado(): void
    {
        $resp = $this->actingAs($this->usuario())->post(route('oraciones.store'), [
            'titulo' => 'Formato inexistente',
            'tipo' => 'Físico', // válido para libros, NO para oraciones
            'precio' => 100,
        ]);

        $resp->assertSessionHasErrors('tipo');
    }

    public function test_inventario_oracion_crea_stock_en_la_entidad_principal(): void
    {
        $principal = Entidad::where('entidad_principal', true)->first();
        $this->assertNotNull($principal, 'Se requiere una entidad principal en la base de testing.');

        $oracion = Oracion::create(['titulo' => 'Oración Test Stock', 'tipo' => 'Audio', 'precio' => 500]);

        $resp = $this->actingAs($this->usuario())->post(route('inventario-oraciones.store'), [
            'oracion_id' => $oracion->id,
            'cantidad' => 7,
        ]);

        $resp->assertRedirect(route('inventario-oraciones.index'));
        $this->assertDatabaseHas('inventario_entidad_oracion', [
            'entidad_id' => $principal->id,
            'oracion_id' => $oracion->id,
            'cantidad' => 7,
        ]);
    }
}

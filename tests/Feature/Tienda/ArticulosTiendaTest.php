<?php

namespace Tests\Feature\Tienda;

use App\Models\ArticuloTienda;
use App\Models\CategoriaTienda;
use App\Models\Entidad;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Módulo Tienda general: categorías dinámicas (CRUD) + catálogo de artículos + inventario por entidad.
 */
class ArticulosTiendaTest extends TestCase
{
    use DatabaseTransactions;

    private function usuario(): User
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);

        return User::factory()->create();
    }

    public function test_crear_categoria_de_tienda(): void
    {
        $resp = $this->actingAs($this->usuario())->post(route('categorias-tienda.store'), [
            'nombre' => 'Sahumerios',
        ]);

        $resp->assertRedirect(route('categorias-tienda.index'));
        $this->assertDatabaseHas('categorias_tienda', ['nombre' => 'Sahumerios']);
    }

    public function test_categoria_duplicada_es_rechazada(): void
    {
        CategoriaTienda::create(['nombre' => 'Zafus']);

        $resp = $this->actingAs($this->usuario())->post(route('categorias-tienda.store'), [
            'nombre' => 'Zafus',
        ]);

        $resp->assertSessionHasErrors('nombre');
    }

    public function test_crear_articulo_asociado_a_categoria(): void
    {
        $categoria = CategoriaTienda::create(['nombre' => 'Adornos']);

        $resp = $this->actingAs($this->usuario())->post(route('articulos-tienda.store'), [
            'titulo' => 'Buda de resina',
            'categoria_tienda_id' => $categoria->id,
            'precio' => 8500,
        ]);

        $resp->assertRedirect(route('articulos-tienda.index'));
        $this->assertDatabaseHas('articulos_tienda', [
            'titulo' => 'Buda de resina',
            'categoria_tienda_id' => $categoria->id,
        ]);
    }

    public function test_articulo_requiere_categoria_existente(): void
    {
        $resp = $this->actingAs($this->usuario())->post(route('articulos-tienda.store'), [
            'titulo' => 'Sin categoría',
            'categoria_tienda_id' => 999999,
            'precio' => 100,
        ]);

        $resp->assertSessionHasErrors('categoria_tienda_id');
    }

    public function test_inventario_articulo_crea_stock_en_la_entidad_principal(): void
    {
        $principal = Entidad::where('entidad_principal', true)->first();
        $this->assertNotNull($principal, 'Se requiere una entidad principal en la base de testing.');

        $categoria = CategoriaTienda::create(['nombre' => 'Sahumerios ' . uniqid()]);
        $articulo = ArticuloTienda::create([
            'titulo' => 'Sahumerio de Sándalo',
            'categoria_tienda_id' => $categoria->id,
            'precio' => 1500,
        ]);

        $resp = $this->actingAs($this->usuario())->post(route('inventario-articulos-tienda.store'), [
            'articulo_tienda_id' => $articulo->id,
            'cantidad' => 12,
        ]);

        $resp->assertRedirect(route('inventario-articulos-tienda.index'));
        $this->assertDatabaseHas('inventario_entidad_articulo_tienda', [
            'entidad_id' => $principal->id,
            'articulo_tienda_id' => $articulo->id,
            'cantidad' => 12,
        ]);
    }

    public function test_no_se_puede_borrar_categoria_con_articulos(): void
    {
        $categoria = CategoriaTienda::create(['nombre' => 'Con artículos ' . uniqid()]);
        ArticuloTienda::create([
            'titulo' => 'Artículo bloqueante',
            'categoria_tienda_id' => $categoria->id,
            'precio' => 100,
        ]);

        $this->actingAs($this->usuario())->delete(route('categorias-tienda.destroy', $categoria->id));

        $this->assertDatabaseHas('categorias_tienda', ['id' => $categoria->id]);
    }
}

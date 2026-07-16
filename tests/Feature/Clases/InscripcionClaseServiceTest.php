<?php

namespace Tests\Feature\Clases;

use App\Models\Ciclo;
use App\Models\Clase;
use App\Models\Entidad;
use App\Models\EsquemaPrecio;
use App\Models\EsquemaPrecioMembresia;
use App\Models\InscripcionClase;
use App\Models\InventarioEntidadLibro;
use App\Models\InventarioEntidadOracion;
use App\Models\Libro;
use App\Models\Membresia;
use App\Models\Moneda;
use App\Models\Oracion;
use App\Services\InscripcionClaseService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Checkout de clases con productos de las 4 categorías Tharpa (tabla hija + diffs de stock por ID).
 */
class InscripcionClaseServiceTest extends TestCase
{
    use DatabaseTransactions;

    private function service(): InscripcionClaseService
    {
        return app(InscripcionClaseService::class);
    }

    private function entidad(): Entidad
    {
        return Entidad::where('entidad_principal', true)->firstOrFail();
    }

    private function claseConPrecio(Entidad $ent, float $precioGeneral = 3000): Clase
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);
        $membresia = Membresia::create(['nombre' => 'Sin membresía', 'valor' => 0]);
        $moneda = Moneda::firstOrCreate(['nombre' => 'Peso'], ['simbolo' => '$']);
        $esquema = EsquemaPrecio::create(['nombre' => 'Esq ' . uniqid()]);
        EsquemaPrecioMembresia::create([
            'esquema_precio_id' => $esquema->id,
            'membresia_id' => $membresia->id,
            'moneda_id' => $moneda->id,
            'precio' => $precioGeneral,
        ]);

        $ciclo = Ciclo::create(['nombre' => 'Ciclo Test', 'mes' => (int) now()->format('m')]);

        return Clase::create([
            'nombre' => 'Clase Test',
            'ciclo_id' => $ciclo->id,
            'entidad_id' => $ent->id,
            'mes_referencia' => now()->format('Y-m'),
            'esquema_precio_id' => $esquema->id,
            'dias_semana' => ['lunes'],
            'horario_desde' => '10:00',
            'horario_hasta' => '11:00',
            'activa' => true,
        ]);
    }

    public function test_alta_con_varias_categorias_descuenta_stock_y_crea_items(): void
    {
        $ent = $this->entidad();
        $clase = $this->claseConPrecio($ent, 3000);

        $libro = Libro::create(['titulo' => 'Libro Clase', 'tipo' => 'Físico', 'precio' => 1000]);
        InventarioEntidadLibro::create(['entidad_id' => $ent->id, 'libro_id' => $libro->id, 'cantidad' => 3]);
        $oracion = Oracion::create(['titulo' => 'Oración Clase', 'tipo' => 'Librillo', 'precio' => 500]);
        InventarioEntidadOracion::create(['entidad_id' => $ent->id, 'oracion_id' => $oracion->id, 'cantidad' => 2]);

        $ins = $this->service()->persistir([
            'email' => 'alumno.clase@example.com',
            'nombre' => 'Alumno Clase',
            'registrar_datos' => true,
            'clase_id' => $clase->id,
            'membresia' => 'Sin membresía',
            'pago' => 'Pendiente',
            'online' => false,
            'libro_ids' => [$libro->id],
            'oracion_ids' => [$oracion->id],
            'montoTienda' => 0,
        ], null, null);

        $this->assertSame(2, (int) InventarioEntidadLibro::where('libro_id', $libro->id)->value('cantidad'));
        $this->assertSame(1, (int) InventarioEntidadOracion::where('oracion_id', $oracion->id)->value('cantidad'));
        $this->assertSame(2, $ins->items()->count());
        $this->assertEquals(3000, (float) $ins->montoActividad);
        $this->assertEquals(1500, (float) $ins->montoTharpa);
        $this->assertEquals(4500, (float) $ins->montoApagar);
    }

    public function test_edicion_quitar_producto_devuelve_stock(): void
    {
        $ent = $this->entidad();
        $clase = $this->claseConPrecio($ent, 3000);

        $libro = Libro::create(['titulo' => 'Libro E', 'tipo' => 'Físico', 'precio' => 1000]);
        InventarioEntidadLibro::create(['entidad_id' => $ent->id, 'libro_id' => $libro->id, 'cantidad' => 3]);
        $oracion = Oracion::create(['titulo' => 'Oración E', 'tipo' => 'Audio', 'precio' => 500]);
        InventarioEntidadOracion::create(['entidad_id' => $ent->id, 'oracion_id' => $oracion->id, 'cantidad' => 2]);

        $base = [
            'email' => 'alumno.edit@example.com',
            'nombre' => 'Alumno Edit',
            'registrar_datos' => true,
            'clase_id' => $clase->id,
            'membresia' => 'Sin membresía',
            'pago' => 'Pendiente',
            'online' => false,
            'montoTienda' => 0,
        ];

        $ins = $this->service()->persistir($base + ['libro_ids' => [$libro->id], 'oracion_ids' => [$oracion->id]], null, null);
        $this->assertSame(2, (int) InventarioEntidadLibro::where('libro_id', $libro->id)->value('cantidad'));

        // Editar: quitar el libro, conservar la oración.
        $ins = InscripcionClase::find($ins->id);
        $this->service()->persistir($base + ['libro_ids' => [], 'oracion_ids' => [$oracion->id]], $ins, null);

        // El libro vuelve al stock; la oración queda igual.
        $this->assertSame(3, (int) InventarioEntidadLibro::where('libro_id', $libro->id)->value('cantidad'));
        $this->assertSame(1, (int) InventarioEntidadOracion::where('oracion_id', $oracion->id)->value('cantidad'));
        $ins->refresh();
        $this->assertSame(1, $ins->items()->count());
        $this->assertEquals(3500, (float) $ins->montoApagar); // 3000 actividad + 500 oración
    }
}

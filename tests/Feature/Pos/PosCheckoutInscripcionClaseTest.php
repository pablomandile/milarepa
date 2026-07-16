<?php

namespace Tests\Feature\Pos;

use App\Models\Ciclo;
use App\Models\Clase;
use App\Models\Entidad;
use App\Models\EsquemaPrecio;
use App\Models\EsquemaPrecioMembresia;
use App\Models\InscripcionClase;
use App\Models\InventarioEntidadLibro;
use App\Models\Libro;
use App\Models\Membresia;
use App\Models\MetodoPago;
use App\Models\Moneda;
use App\Models\User;
use App\Models\VentaPos;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * POS: línea de inscripción a clase (crea la InscripcionClase, descuenta stock de sus
 * productos y la cobra por el ticket → queda Saldada).
 */
class PosCheckoutInscripcionClaseTest extends TestCase
{
    use DatabaseTransactions;

    private function usuario(): User
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);

        return User::factory()->create();
    }

    private function claseConPrecio(Entidad $ent, float $precioGeneral = 3000): Clase
    {
        $membresia = Membresia::create(['nombre' => 'Sin membresía', 'valor' => 0]);
        $moneda = Moneda::firstOrCreate(['nombre' => 'Peso'], ['simbolo' => '$']);
        $esquema = EsquemaPrecio::create(['nombre' => 'Esq ' . uniqid()]);
        EsquemaPrecioMembresia::create([
            'esquema_precio_id' => $esquema->id,
            'membresia_id' => $membresia->id,
            'moneda_id' => $moneda->id,
            'precio' => $precioGeneral,
        ]);
        $ciclo = Ciclo::create(['nombre' => 'Ciclo', 'mes' => (int) now()->format('m')]);

        return Clase::create([
            'nombre' => 'Clase POS',
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

    public function test_pos_inscribe_a_clase_y_la_deja_saldada(): void
    {
        $ent = Entidad::where('entidad_principal', true)->firstOrFail();
        $metodo = MetodoPago::firstOrCreate(['nombre' => 'Efectivo'], ['descripcion' => 'Efectivo', 'tipo_de_pago' => 'Presencial']);
        $clase = $this->claseConPrecio($ent, 3000);
        $libro = Libro::create(['titulo' => 'Libro POS Clase', 'tipo' => 'Físico', 'precio' => 1000]);
        InventarioEntidadLibro::create(['entidad_id' => $ent->id, 'libro_id' => $libro->id, 'cantidad' => 3]);

        $resp = $this->actingAs($this->usuario())->post(route('pos.store'), [
            'entidad_id' => $ent->id,
            'metodo_pago_id' => $metodo->id,
            'items' => [[
                'tipo' => 'inscripcion_clase',
                'cantidad' => 1,
                'inscripcion' => [
                    'email' => 'alumno.pos@example.com',
                    'nombre' => 'Alumno POS',
                    'registrar_datos' => true,
                    'clase_id' => $clase->id,
                    'membresia' => 'Sin membresía',
                    'pago' => 'Pendiente',
                    'online' => false,
                    'libro_ids' => [$libro->id],
                    'montoTienda' => 0,
                ],
            ]],
        ]);

        $resp->assertRedirect(route('pos.index'));

        $ins = InscripcionClase::where('clase_id', $clase->id)->latest('id')->first();
        $this->assertNotNull($ins);
        $this->assertSame('Saldado', $ins->pago);
        $this->assertEquals(4000, (float) $ins->montoApagar); // 3000 + 1000 libro
        $this->assertSame(2, (int) InventarioEntidadLibro::where('libro_id', $libro->id)->value('cantidad'));

        $venta = VentaPos::latest('id')->first();
        $this->assertEquals(4000, (float) $venta->total);

        $this->assertDatabaseHas('cobros', [
            'cobrable_type' => 'inscripcion_clase',
            'cobrable_id' => $ins->id,
            'origen' => 'pos',
            'referencia' => "Por POS #{$venta->id}",
            'monto' => 4000,
        ]);
    }
}

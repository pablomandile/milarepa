<?php

namespace Tests\Feature\Pos;

use App\Models\Actividad;
use App\Models\Entidad;
use App\Models\EsquemaPrecio;
use App\Models\EsquemaPrecioMembresia;
use App\Models\Grabacion;
use App\Models\Inscripcion;
use App\Models\Membresia;
use App\Models\MetodoPago;
use App\Models\Modalidad;
use App\Models\Moneda;
use App\Models\TipoActividad;
use App\Models\User;
use App\Models\VentaPos;
use App\Services\InscripcionActividadService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * POS: inscripción a actividad (crea la Inscripcion vía InscripcionActividadService y la
 * cobra por el ticket → Saldada/Confirmada). El checkout público de actividades no se toca.
 */
class PosCheckoutInscripcionActividadTest extends TestCase
{
    use DatabaseTransactions;

    private function usuario(): User
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);

        return User::factory()->create();
    }

    private function actividadConPrecio(float $precioGeneral = 5000, float $grabacionValor = 2000): Actividad
    {
        $membresia = Membresia::create(['nombre' => 'Sin membresía', 'valor' => 0]);
        $moneda = Moneda::firstOrCreate(['nombre' => 'Peso'], ['simbolo' => '$']);
        $esquema = EsquemaPrecio::create(['nombre' => 'Esq Act ' . uniqid()]);
        EsquemaPrecioMembresia::create([
            'esquema_precio_id' => $esquema->id,
            'membresia_id' => $membresia->id,
            'moneda_id' => $moneda->id,
            'precio' => $precioGeneral,
        ]);
        $grabacion = Grabacion::create(['nombre' => 'Grabación', 'valor' => $grabacionValor]);

        return Actividad::create([
            'nombre' => 'Evento POS',
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo POS'])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Presencial'])->id,
            'esquema_precio_id' => $esquema->id,
            'grabacion_id' => $grabacion->id,
            'entidad_id' => DB::table('entidades')->value('id'),
            'fecha_inicio' => '2026-08-01 10:00:00',
            'fecha_fin' => '2026-08-02 20:00:00',
            'estado' => true,
        ]);
    }

    public function test_servicio_crea_inscripcion_con_montos(): void
    {
        $this->usuario();
        $act = $this->actividadConPrecio(5000, 2000);
        $service = app(InscripcionActividadService::class);

        $ins = $service->crearDesdePayload([
            'actividad_id' => $act->id,
            'email' => 'part.act@example.com',
            'nombre' => 'Participante',
            'registrar_datos' => true,
            'incluye_grabacion' => true,
        ], null);

        $this->assertEquals(5000, (float) $ins->montoActividad);
        $this->assertEquals(7000, (float) $ins->montoapagar); // 5000 + 2000 grabación
        $this->assertSame($act->id, $ins->actividad_id);
    }

    public function test_cotizar_devuelve_monto_sin_persistir(): void
    {
        $act = $this->actividadConPrecio(5000, 2000);
        $insAntes = Inscripcion::count();

        $cot = app(InscripcionActividadService::class)->cotizar([
            'actividad_id' => $act->id,
            'email' => 'nuevo@example.com',
            'incluye_grabacion' => false,
        ]);

        $this->assertEquals(5000, (float) $cot['montoApagar']);
        $this->assertSame($insAntes, Inscripcion::count());
    }

    public function test_pos_inscribe_a_actividad_y_la_deja_saldada(): void
    {
        $ent = Entidad::where('entidad_principal', true)->firstOrFail();
        $metodo = MetodoPago::firstOrCreate(['nombre' => 'Efectivo'], ['descripcion' => 'Efectivo', 'tipo_de_pago' => 'Presencial']);
        $act = $this->actividadConPrecio(5000, 2000);

        $resp = $this->actingAs($this->usuario())->post(route('pos.store'), [
            'entidad_id' => $ent->id,
            'metodo_pago_id' => $metodo->id,
            'items' => [[
                'tipo' => 'inscripcion_actividad',
                'cantidad' => 1,
                'inscripcion' => [
                    'actividad_id' => $act->id,
                    'email' => 'alumno.act.pos@example.com',
                    'nombre' => 'Alumno Act',
                    'registrar_datos' => true,
                    'incluye_grabacion' => false,
                ],
            ]],
        ]);

        $resp->assertRedirect(route('pos.index'));

        $ins = Inscripcion::where('actividad_id', $act->id)->latest('id')->first();
        $this->assertNotNull($ins);
        $this->assertSame('Saldado', $ins->pago);
        $this->assertSame('Confirmada', $ins->estado);
        $this->assertEquals(5000, (float) $ins->montoapagar);

        $venta = VentaPos::latest('id')->first();
        $this->assertEquals(5000, (float) $venta->total);
        $this->assertDatabaseHas('cobros', [
            'cobrable_type' => 'inscripcion',
            'cobrable_id' => $ins->id,
            'origen' => 'pos',
            'referencia' => "Por POS #{$venta->id}",
            'monto' => 5000,
        ]);
    }
}

<?php

namespace Tests\Feature\Cobros;

use App\Models\Actividad;
use App\Models\Cobro;
use App\Models\EsquemaPrecio;
use App\Models\EsquemaPrecioMembresia;
use App\Models\Imagen;
use App\Models\Inscripcion;
use App\Models\Membresia;
use App\Models\Modalidad;
use App\Models\TipoActividad;
use App\Models\User;
use App\Services\CobroService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Nunca hay comprobante sin cobro: subir un comprobante crea (o alimenta) un cobro
 * `a_revisar` con el archivo enlazado. El cobro a revisar NO suma como plata recibida
 * (montoCobrado/saldoPendiente sólo cuentan confirmados) y el staging
 * `inscripcion_comprobantes` ya no recibe escrituras.
 */
class CobroARevisarTest extends TestCase
{
    use DatabaseTransactions;

    private function inscripcion(float $monto = 10000, ?User $user = null): Inscripcion
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);

        $general = Membresia::create(['nombre' => 'Sin membresía', 'valor' => 0]);
        $esquema = EsquemaPrecio::create(['nombre' => 'Esquema A Revisar']);
        EsquemaPrecioMembresia::create([
            'esquema_precio_id' => $esquema->id,
            'membresia_id' => $general->id,
            'moneda_id' => DB::table('monedas')->value('id'),
            'precio' => $monto,
        ]);

        $actividad = Actividad::create([
            'nombre' => 'Evento A Revisar',
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo AR'])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Presencial AR'])->id,
            'esquema_precio_id' => $esquema->id,
            'entidad_id' => DB::table('entidades')->value('id'),
            'fecha_inicio' => '2026-09-01 10:00:00',
            'fecha_fin' => '2026-09-02 20:00:00',
            'estado' => true,
        ]);

        return Inscripcion::create([
            'actividad_id' => $actividad->id,
            'user_id' => ($user ?? User::factory()->create())->id,
            'membresia' => 'Sin membresía',
            'precioGeneral' => $monto,
            'montoActividad' => $monto,
            'montoapagar' => $monto,
            'pago' => 'Pendiente',
            'estado' => 'Registrada',
            'envioLinkStream' => 'No aplica',
            'asistencia' => 'Pendiente',
            'online' => false,
        ]);
    }

    private function imagen(string $nombre): Imagen
    {
        return Imagen::create(['nombre' => $nombre, 'ruta' => "comprobantes/{$nombre}"]);
    }

    public function test_subida_de_owner_crea_cobro_a_revisar_origen_checkout(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create();
        $inscripcion = $this->inscripcion(10000, $owner);

        $this->actingAs($owner)
            ->post(route('inscripciones.comprobante', ['inscripcion' => $inscripcion->id]), [
                'comprobante' => UploadedFile::fake()->image('transferencia.png', 600, 600),
                'descripcion' => 'Transferencia agosto',
            ])
            ->assertRedirect();

        $inscripcion->refresh();
        $cobros = $inscripcion->cobros()->get();
        $this->assertCount(1, $cobros);

        $cobro = $cobros->first();
        $this->assertSame(Cobro::ESTADO_A_REVISAR, $cobro->estado);
        $this->assertSame('checkout', $cobro->origen);
        $this->assertNull($cobro->fecha_pago);
        $this->assertEquals(10000, (float) $cobro->monto);
        $this->assertSame(1, $cobro->comprobantes()->count());
        $this->assertSame('Transferencia agosto', $cobro->comprobantes()->first()->descripcion);

        // No suma como plata recibida ni cambia la caché de pago.
        $this->assertEquals(0.0, $inscripcion->montoCobrado());
        $this->assertEquals(10000.0, $inscripcion->saldoPendiente());
        $this->assertSame('Pendiente', $inscripcion->pago);

        // El staging ya no recibe escrituras.
        $this->assertSame(0, $inscripcion->comprobantes()->count());
    }

    public function test_subida_de_admin_lleva_origen_manual_y_registrador(): void
    {
        Storage::fake('public');
        $inscripcion = $this->inscripcion();
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('inscripciones.comprobante', ['inscripcion' => $inscripcion->id]), [
                'comprobante' => UploadedFile::fake()->image('admin.png', 600, 600),
            ])
            ->assertRedirect();

        $cobro = $inscripcion->cobros()->first();
        $this->assertSame(Cobro::ESTADO_A_REVISAR, $cobro->estado);
        $this->assertSame('manual', $cobro->origen);
        $this->assertEquals($admin->id, $cobro->registrado_por);
    }

    public function test_segunda_subida_se_agrega_al_mismo_cobro_a_revisar(): void
    {
        $inscripcion = $this->inscripcion();
        $svc = app(CobroService::class);

        $svc->registrarComprobanteARevisar($inscripcion, $this->imagen('uno.jpg')->id, null, 'checkout');
        $svc->registrarComprobanteARevisar($inscripcion, $this->imagen('dos.jpg')->id, null, 'checkout');

        $cobros = $inscripcion->cobros()->get();
        $this->assertCount(1, $cobros);
        $this->assertSame(2, $cobros->first()->comprobantes()->count());
    }

    public function test_subida_a_inscripcion_saldada_adjunta_al_cobro_confirmado(): void
    {
        $inscripcion = $this->inscripcion(10000);
        $svc = app(CobroService::class);

        $confirmado = $svc->registrar($inscripcion, ['monto' => 10000, 'origen' => 'manual']);
        $inscripcion->refresh();
        $this->assertSame('Saldado', $inscripcion->pago);

        $resultado = $svc->registrarComprobanteARevisar($inscripcion, $this->imagen('tarde.jpg')->id, null, 'checkout');

        // No inventa deuda: documenta el cobro confirmado existente.
        $this->assertSame($confirmado->id, $resultado->id);
        $this->assertSame(0, $inscripcion->cobros()->aRevisar()->count());
        $this->assertSame(1, $confirmado->comprobantes()->count());
    }

    public function test_finalizar_pago_del_grid_con_comprobante_crea_cobro_a_revisar(): void
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);
        $inscripcionBase = $this->inscripcion(10000); // arma actividad+esquema
        $actividad = $inscripcionBase->actividad;
        $user = User::factory()->create();

        $resp = $this->withSession(['grid_pago' => [
            'actividad_id' => $actividad->id,
            'user_id' => $user->id,
            'guest' => null,
            'comprobante_path' => 'comprobantes/grid-transfer.jpg',
            'comprobante_descripcion' => 'Transferencia grid',
            'pago_metodo' => 'comprobante',
        ]])->postJson('/grid-actividades/pago/finalizar', [
            'pago_metodo' => 'comprobante',
            'invitados' => [],
        ]);

        $resp->assertOk()->assertJson(['ok' => true]);

        $inscripcion = Inscripcion::find($resp->json('inscripcion_id'));
        $cobro = $inscripcion->cobros()->first();

        $this->assertNotNull($cobro);
        $this->assertSame(Cobro::ESTADO_A_REVISAR, $cobro->estado);
        $this->assertSame('checkout', $cobro->origen);
        $this->assertSame('comprobantes/grid-transfer.jpg', $cobro->comprobantes()->with('imagen')->first()->ruta);
        $this->assertEquals(0.0, $inscripcion->montoCobrado());
        $this->assertSame('Pendiente', $inscripcion->pago);
        $this->assertSame(0, $inscripcion->comprobantes()->count());
    }
}

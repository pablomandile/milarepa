<?php

namespace Tests\Feature\Invitados;

use App\Http\Controllers\GridActividadesController;
use App\Models\Actividad;
use App\Models\Comida;
use App\Models\EsquemaPrecio;
use App\Models\EsquemaPrecioMembresia;
use App\Models\Inscripcion;
use App\Models\Membresia;
use App\Models\Modalidad;
use App\Models\TipoActividad;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Promoción 2x1 ("van dos, paga uno"): la inscripción exige un acompañante y el
 * PRIMERO no paga la actividad; sus servicios sí se cobran. Los siguientes
 * invitados pagan precio general, como siempre.
 */
class Promo2x1Test extends TestCase
{
    use DatabaseTransactions;

    /** @return array{0: Actividad, 1: Comida} */
    private function actividad(bool $promo = true, string $modalidad = 'Presencial 2x1 Test'): array
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);

        $general = Membresia::create(['nombre' => 'Sin membresía', 'valor' => 0]);
        $esquema = EsquemaPrecio::create(['nombre' => 'Esquema 2x1 Test']);
        EsquemaPrecioMembresia::create([
            'esquema_precio_id' => $esquema->id,
            'membresia_id' => $general->id,
            'moneda_id' => DB::table('monedas')->value('id'),
            'precio' => 10000,
        ]);

        $comida = Comida::create(['nombre' => 'Almuerzo 2x1', 'descripcion' => 'x', 'precio' => 1500, 'vegano' => false, 'celiaco' => false]);

        $actividad = Actividad::create([
            'nombre' => 'Curso Día del Amigo',
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo 2x1 Test'])->id,
            'modalidad_id' => Modalidad::create(['nombre' => $modalidad])->id,
            'esquema_precio_id' => $esquema->id,
            'entidad_id' => DB::table('entidades')->value('id'),
            'fecha_inicio' => '2026-12-20 10:00:00',
            'fecha_fin' => '2026-12-20 13:00:00',
            'estado' => true,
            'promo_2x1' => $promo,
        ]);
        $actividad->comidas()->attach($comida->id);

        return [$actividad, $comida];
    }

    private function usuario(): User
    {
        return User::create([
            'name' => 'Titular 2x1',
            'email' => 'titular.2x1.' . uniqid() . '@example.com',
            'password' => Hash::make('x'),
        ]);
    }

    private function sesionPago(Actividad $actividad, User $user, ?int $inscripcionId = null): array
    {
        $pago = [
            'actividad_id' => $actividad->id,
            'user_id' => $user->id,
            'guest' => null,
            'comprobante_path' => null,
            'pago_metodo' => null,
        ];
        if ($inscripcionId) {
            $pago['inscripcion_id'] = $inscripcionId;
        }

        return ['grid_pago' => $pago];
    }

    private function finalizar(Actividad $actividad, User $user, array $invitados)
    {
        return $this->withSession($this->sesionPago($actividad, $user))
            ->postJson('/grid-actividades/pago/finalizar', [
                'pago_metodo' => 'efectivo',
                'invitados' => $invitados,
            ]);
    }

    public function test_sin_acompanante_rechaza_la_inscripcion(): void
    {
        [$actividad] = $this->actividad();

        $this->finalizar($actividad, $this->usuario(), [])
            ->assertStatus(422)
            ->assertJson(['ok' => false, 'message' => GridActividadesController::MSG_FALTA_ACOMPANANTE]);

        $this->assertSame(0, Inscripcion::where('actividad_id', $actividad->id)->count());
    }

    public function test_el_acompanante_no_paga_la_actividad_pero_si_sus_servicios(): void
    {
        [$actividad, $comida] = $this->actividad();

        $resp = $this->finalizar($actividad, $this->usuario(), [
            ['nombre' => 'Amigo', 'apellido' => 'Uno', 'comidas_ids' => [$comida->id]],
        ])->assertOk()->assertJson(['ok' => true]);

        $inscripcion = Inscripcion::with('invitados')->find($resp->json('inscripcion_id'));
        $acompanante = $inscripcion->invitados->first();

        $this->assertEquals(0, (float) $acompanante->montoActividad);
        $this->assertEquals(1500, (float) $acompanante->montoapagar);
        $this->assertEquals(1500, (float) $inscripcion->monto_invitados);
        // Titular 10000 + comida del acompañante 1500.
        $this->assertEquals(11500, (float) $inscripcion->montoapagar);
    }

    public function test_desde_el_segundo_invitado_se_paga_precio_general(): void
    {
        [$actividad] = $this->actividad();

        $resp = $this->finalizar($actividad, $this->usuario(), [
            ['nombre' => 'Amigo', 'apellido' => 'Uno'],
            ['nombre' => 'Amigo', 'apellido' => 'Dos'],
        ])->assertOk();

        $inscripcion = Inscripcion::with('invitados')->find($resp->json('inscripcion_id'));
        $this->assertEquals(0, (float) $inscripcion->invitados->firstWhere('apellido', 'Uno')->montoapagar);
        $this->assertEquals(10000, (float) $inscripcion->invitados->firstWhere('apellido', 'Dos')->montoapagar);
        $this->assertEquals(20000, (float) $inscripcion->montoapagar);
    }

    public function test_sin_promo_no_exige_acompanante_y_el_invitado_paga_precio_general(): void
    {
        [$actividad] = $this->actividad(false);
        $user = $this->usuario();

        $this->finalizar($actividad, $user, [])->assertOk();

        [$otra] = $this->actividad(false);
        $resp = $this->finalizar($otra, $this->usuario(), [['nombre' => 'Inv', 'apellido' => 'Normal']])->assertOk();
        $invitado = Inscripcion::with('invitados')->find($resp->json('inscripcion_id'))->invitados->first();
        $this->assertEquals(10000, (float) $invitado->montoActividad);
    }

    public function test_en_actividad_solo_online_no_se_exige_acompanante(): void
    {
        [$actividad] = $this->actividad(true, 'Online');

        $this->finalizar($actividad, $this->usuario(), [])->assertOk()->assertJson(['ok' => true]);
    }

    public function test_la_edicion_admin_aplica_el_precio_2x1(): void
    {
        [$actividad] = $this->actividad();
        $resp = $this->finalizar($actividad, $this->usuario(), [['nombre' => 'Amigo', 'apellido' => 'Uno']])->assertOk();
        $inscripcion = Inscripcion::find($resp->json('inscripcion_id'));

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->putJson(route('estadoinscripciones.update', $inscripcion->id), [
            'pago' => 'Pendiente',
            'invitados' => [
                ['nombre' => 'Amigo', 'apellido' => 'Uno'],
                ['nombre' => 'Amigo', 'apellido' => 'Dos'],
            ],
        ])->assertOk();

        $inscripcion->refresh()->load('invitados');
        $this->assertEquals(0, (float) $inscripcion->invitados->firstWhere('apellido', 'Uno')->montoapagar);
        $this->assertEquals(10000, (float) $inscripcion->invitados->firstWhere('apellido', 'Dos')->montoapagar);
        $this->assertEquals(20000, (float) $inscripcion->montoapagar);
    }

    public function test_pagar_una_inscripcion_existente_precarga_sus_invitados(): void
    {
        [$actividad, $comida] = $this->actividad();
        $user = $this->usuario();
        $resp = $this->finalizar($actividad, $user, [
            ['nombre' => 'Amigo', 'apellido' => 'Uno', 'comidas_ids' => [$comida->id]],
        ])->assertOk();
        $inscripcionId = (int) $resp->json('inscripcion_id');

        $this->actingAs($user)
            ->withSession($this->sesionPago($actividad, $user, $inscripcionId))
            ->get("/grid-actividades/pago/{$actividad->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('GridActividades/Pago')
                ->where('actividad.promo_2x1', true)
                ->has('inscripcion.invitados', 1)
                ->where('inscripcion.invitados.0.apellido', 'Uno')
                ->where('inscripcion.invitados.0.comidas.0.id', $comida->id)
            );
    }
}

<?php

namespace Tests\Feature\Invitados;

use App\Models\Actividad;
use App\Models\Comida;
use App\Models\EsquemaPrecio;
use App\Models\EsquemaPrecioMembresia;
use App\Models\Grabacion;
use App\Models\Inscripcion;
use App\Models\Membresia;
use App\Models\MembresiaUsuario;
use App\Models\Modalidad;
use App\Models\TipoActividad;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Pantalla de pago: agregar invitados a la inscripción del asistente.
 * Cubre: precio general forzado (sin descuento) para invitados, suma al total,
 * regla de modalidad online, y tope de 10 invitados.
 */
class FinalizarPagoConInvitadosTest extends TestCase
{
    use DatabaseTransactions;

    private function asegurarPaisDefault(): void
    {
        DB::statement(
            'INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())',
            ['Argentina'],
        );
    }

    /**
     * Crea una actividad con esquema de precios (general 10000 / TK Corazón 6000),
     * grabación y una comida. Devuelve [actividad, membresiaCorazon, grabacion, comida].
     */
    private function escenario(string $modalidad = 'Presencial Test'): array
    {
        $this->asegurarPaisDefault();

        $general = Membresia::create(['nombre' => 'Sin membresía', 'valor' => 0]);
        $corazon = Membresia::create(['nombre' => 'TK Corazón', 'valor' => 55000]);

        $monedaId = DB::table('monedas')->value('id');

        $esquema = EsquemaPrecio::create(['nombre' => 'Esquema Invitados Test']);
        EsquemaPrecioMembresia::create([
            'esquema_precio_id' => $esquema->id,
            'membresia_id' => $general->id,
            'moneda_id' => $monedaId,
            'precio' => 10000,
        ]);
        EsquemaPrecioMembresia::create([
            'esquema_precio_id' => $esquema->id,
            'membresia_id' => $corazon->id,
            'moneda_id' => $monedaId,
            'precio' => 6000,
        ]);

        $grabacion = Grabacion::create(['nombre' => 'Grabación Test', 'valor' => 2000]);
        $comida = Comida::create(['nombre' => 'Almuerzo Test', 'descripcion' => 'Almuerzo', 'precio' => 1500, 'vegano' => false, 'celiaco' => false]);

        $actividad = Actividad::create([
            'nombre' => 'Evento Invitados Test',
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo Invitados'])->id,
            'modalidad_id' => Modalidad::create(['nombre' => $modalidad])->id,
            'esquema_precio_id' => $esquema->id,
            'grabacion_id' => $grabacion->id,
            'entidad_id' => DB::table('entidades')->value('id'),
            'fecha_inicio' => '2026-07-01 10:00:00',
            'fecha_fin' => '2026-07-02 20:00:00',
            'estado' => true,
        ]);
        $actividad->comidas()->attach($comida->id);

        return [$actividad, $corazon, $grabacion, $comida];
    }

    private function principalConMembresia(Membresia $membresia): User
    {
        $user = User::create([
            'name' => 'Principal Test',
            'email' => 'principal.invitados@example.com',
            'password' => Hash::make('x'),
        ]);
        // La membresía del usuario se resuelve vía el perfil membresia_usuario (no hay columna en users).
        MembresiaUsuario::create([
            'user_id' => $user->id,
            'membresia_id' => $membresia->id,
            'membresia_inscripcion_fecha' => '2026-01-01',
            'suscripcion' => false,
            'membresia_online' => false,
            'info_tarjetas_kadampa' => false,
        ]);

        return $user->fresh();
    }

    private function finalizar(Actividad $actividad, User $user, array $invitados, array $extra = [])
    {
        return $this->withSession(['grid_pago' => [
            'actividad_id' => $actividad->id,
            'user_id' => $user->id,
            'guest' => null,
            'comprobante_path' => null,
            'pago_metodo' => null,
        ]])->postJson('/grid-actividades/pago/finalizar', array_merge([
            'pago_metodo' => 'efectivo',
            'invitados' => $invitados,
        ], $extra));
    }

    public function test_invitado_paga_precio_general_sin_descuento_y_suma_al_total(): void
    {
        [$actividad, $corazon, , $comida] = $this->escenario();
        $user = $this->principalConMembresia($corazon);

        $resp = $this->finalizar($actividad, $user, [
            ['nombre' => 'Inv', 'apellido' => 'Uno', 'incluye_grabacion' => true, 'comidas_ids' => [$comida->id]],
            ['nombre' => 'Inv', 'apellido' => 'Dos'],
        ]);

        $resp->assertOk()->assertJson(['ok' => true]);

        $inscripcion = Inscripcion::with('invitados')->find($resp->json('inscripcion_id'));
        $this->assertNotNull($inscripcion);

        // Principal con membresía paga 6000 (precio membresía).
        $this->assertEquals(6000, (float) $inscripcion->montoActividad);

        // monto_invitados = (10000 + 2000 grabación + 1500 comida) + 10000 = 23500
        $this->assertEquals(23500, (float) $inscripcion->monto_invitados);
        // Total = principal (6000) + invitados (23500).
        $this->assertEquals(29500, (float) $inscripcion->montoapagar);

        $this->assertCount(2, $inscripcion->invitados);
        $uno = $inscripcion->invitados->firstWhere('apellido', 'Uno');
        // El invitado paga precio GENERAL (10000), no el de membresía (6000).
        $this->assertEquals(10000, (float) $uno->montoActividad);
        $this->assertEquals(13500, (float) $uno->montoapagar);
        $this->assertEquals(2000, (float) $uno->montoGrabacion);
        $this->assertEqualsCanonicalizing([$comida->id], $uno->comidas->pluck('id')->all());

        $dos = $inscripcion->invitados->firstWhere('apellido', 'Dos');
        $this->assertEquals(10000, (float) $dos->montoActividad);
        $this->assertEquals(10000, (float) $dos->montoapagar);
    }

    public function test_invitado_puede_ser_online_si_la_actividad_es_presencial_y_online_abierta(): void
    {
        [$actividad, $corazon] = $this->escenario('Presencial y Online Abierta');
        $user = $this->principalConMembresia($corazon);

        $resp = $this->finalizar($actividad, $user, [
            ['nombre' => 'Inv', 'apellido' => 'Online', 'online' => true],
        ]);

        $resp->assertOk();
        $inscripcion = Inscripcion::with('invitados')->find($resp->json('inscripcion_id'));
        $this->assertTrue((bool) $inscripcion->invitados->first()->online);
    }

    public function test_invitado_online_se_fuerza_a_falso_si_la_actividad_no_es_abierta(): void
    {
        [$actividad, $corazon] = $this->escenario('Presencial y Online Cerrada');
        $user = $this->principalConMembresia($corazon);

        $resp = $this->finalizar($actividad, $user, [
            ['nombre' => 'Inv', 'apellido' => 'NoOnline', 'online' => true],
        ]);

        $resp->assertOk();
        $inscripcion = Inscripcion::with('invitados')->find($resp->json('inscripcion_id'));
        $this->assertFalse((bool) $inscripcion->invitados->first()->online);
    }

    public function test_rechaza_mas_de_diez_invitados(): void
    {
        [$actividad, $corazon] = $this->escenario();
        $user = $this->principalConMembresia($corazon);

        $invitados = [];
        for ($i = 0; $i < 11; $i++) {
            $invitados[] = ['nombre' => 'Inv', 'apellido' => 'N' . $i];
        }

        $resp = $this->finalizar($actividad, $user, $invitados);

        $resp->assertStatus(422);
        $this->assertSame(0, Inscripcion::where('actividad_id', $actividad->id)->count());
    }
}

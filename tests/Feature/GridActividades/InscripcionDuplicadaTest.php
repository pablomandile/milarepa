<?php

namespace Tests\Feature\GridActividades;

use App\Http\Controllers\GridActividadesController;
use App\Models\Actividad;
use App\Models\EsquemaPrecio;
use App\Models\EsquemaPrecioMembresia;
use App\Models\GuestUser;
use App\Models\Inscripcion;
use App\Models\Membresia;
use App\Models\Modalidad;
use App\Models\TipoActividad;
use App\Models\User;
use App\Support\UserLookupToken;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Bloqueo de inscripciones duplicadas en el checkout público: los guards de
 * preparePago / pago / finalizarPago rechazan con "Ya estás inscripto a esta
 * actividad." en todos los caminos (token, autenticado, guest por email), y la
 * exclusión de `inscripcion_id` mantiene vivo el flujo update ("Pagar" de Mis
 * inscripciones, que antes quedaba bloqueado por su propia inscripción).
 */
class InscripcionDuplicadaTest extends TestCase
{
    use DatabaseTransactions;

    private function actividad(float $monto = 10000): Actividad
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);
        DB::statement('INSERT IGNORE INTO provincias (id, nombre) VALUES (1, ?)', ['Buenos Aires']);

        $general = Membresia::create(['nombre' => 'Sin membresía', 'valor' => 0]);
        $esquema = EsquemaPrecio::create(['nombre' => 'Esquema Duplicados']);
        EsquemaPrecioMembresia::create([
            'esquema_precio_id' => $esquema->id,
            'membresia_id' => $general->id,
            'moneda_id' => DB::table('monedas')->value('id'),
            'precio' => $monto,
        ]);

        return Actividad::create([
            'nombre' => 'Evento Duplicados',
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo Dup'])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Presencial Dup'])->id,
            'esquema_precio_id' => $esquema->id,
            'entidad_id' => DB::table('entidades')->value('id'),
            'fecha_inicio' => '2026-09-01 10:00:00',
            'fecha_fin' => '2026-09-02 20:00:00',
            'estado' => true,
        ]);
    }

    private function inscribir(Actividad $actividad, ?User $user = null, ?GuestUser $guestUser = null): Inscripcion
    {
        return Inscripcion::create([
            'actividad_id' => $actividad->id,
            'user_id' => ($user ?? $this->guestOwner())->id,
            'guest_user_id' => $guestUser?->id,
            'membresia' => 'Sin membresía',
            'precioGeneral' => 10000,
            'montoActividad' => 10000,
            'montoapagar' => 10000,
            'pago' => 'Pendiente',
            'estado' => 'Registrada',
            'envioLinkStream' => 'No aplica',
            'asistencia' => 'Pendiente',
            'online' => false,
        ]);
    }

    private function guestOwner(): User
    {
        return User::firstOrCreate(
            ['email' => 'guest@milarepa.local'],
            ['name' => 'Invitado', 'password' => Hash::make(Str::random(32))]
        );
    }

    private function guest(string $email, string $nombre = 'Invitada Test'): GuestUser
    {
        return GuestUser::create([
            'name' => $nombre,
            'email' => $email,
            'pais_id' => 1,
            'provincia_id' => 1,
        ]);
    }

    private function payloadGuest(string $email): array
    {
        return [
            'name' => 'Guest Nuevo',
            'email' => $email,
            'pais_id' => 1,
            'provincia_id' => 1,
            'registrar_datos' => false,
        ];
    }

    // --- preparePago -------------------------------------------------------

    public function test_prepare_pago_con_token_rechaza_usuario_ya_inscripto(): void
    {
        $actividad = $this->actividad();
        $user = User::factory()->create();
        $this->inscribir($actividad, $user);

        $this->postJson('/grid-actividades/pago/prepare', [
            'actividad_id' => $actividad->id,
            'user_lookup_token' => UserLookupToken::issue($user->id),
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', GridActividadesController::MSG_YA_INSCRIPTO);
    }

    public function test_prepare_pago_autenticado_rechaza_usuario_ya_inscripto(): void
    {
        $actividad = $this->actividad();
        $user = User::factory()->create();
        $this->inscribir($actividad, $user);

        $resp = $this->actingAs($user)->postJson('/grid-actividades/pago/prepare', [
            'actividad_id' => $actividad->id,
        ]);

        $resp->assertStatus(422);
        $this->assertStringStartsWith(GridActividadesController::MSG_YA_INSCRIPTO, $resp->json('message'));
    }

    public function test_prepare_pago_guest_rechaza_email_ya_inscripto_case_insensitive(): void
    {
        $actividad = $this->actividad();
        // Dato histórico "sucio": email con mayúsculas y espacios en la BD.
        $guestUser = $this->guest('  Maria.Perez@Example.com  ');
        $this->inscribir($actividad, null, $guestUser);

        $this->postJson('/grid-actividades/pago/prepare', [
            'actividad_id' => $actividad->id,
            'guest' => $this->payloadGuest('maria.perez@example.com'),
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', GridActividadesController::MSG_YA_INSCRIPTO);
    }

    public function test_prepare_pago_guest_rechaza_email_de_usuario_registrado_ya_inscripto(): void
    {
        $actividad = $this->actividad();
        $user = User::factory()->create();
        $this->inscribir($actividad, $user);

        // "Ya estás inscripto" gana sobre "correo ya registrado".
        $this->postJson('/grid-actividades/pago/prepare', [
            'actividad_id' => $actividad->id,
            'guest' => $this->payloadGuest($user->email) + ['registrar_datos' => true],
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', GridActividadesController::MSG_YA_INSCRIPTO);
    }

    // --- finalizarPago -----------------------------------------------------

    public function test_finalizar_pago_guest_duplicado_no_crea_guest_user_ni_inscripcion(): void
    {
        $actividad = $this->actividad();
        $guestUser = $this->guest('dup@example.com');
        $this->inscribir($actividad, null, $guestUser);

        $guestsAntes = GuestUser::count();
        $inscripcionesAntes = Inscripcion::where('actividad_id', $actividad->id)->count();

        $this->withSession(['grid_pago' => [
            'actividad_id' => $actividad->id,
            'user_id' => null,
            'guest' => $this->payloadGuest('dup@example.com'),
            'comprobante_path' => null,
            'pago_metodo' => null,
        ]])->postJson('/grid-actividades/pago/finalizar', [
            'pago_metodo' => 'efectivo',
            'invitados' => [],
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', GridActividadesController::MSG_YA_INSCRIPTO);

        $this->assertSame($guestsAntes, GuestUser::count());
        $this->assertSame($inscripcionesAntes, Inscripcion::where('actividad_id', $actividad->id)->count());
    }

    public function test_finalizar_pago_actualiza_inscripcion_existente_desde_mis_inscripciones(): void
    {
        $actividad = $this->actividad();
        $user = User::factory()->create();
        $inscripcion = $this->inscribir($actividad, $user);

        // Sesión que arma InscripcionesController::preparePago ("Pagar" en Mis inscripciones).
        $this->actingAs($user)->withSession(['grid_pago' => [
            'actividad_id' => $actividad->id,
            'user_id' => $user->id,
            'guest' => null,
            'inscripcion_id' => $inscripcion->id,
            'comprobante_path' => null,
            'pago_metodo' => null,
        ]])->postJson('/grid-actividades/pago/finalizar', [
            'pago_metodo' => 'efectivo',
            'invitados' => [],
        ])
            ->assertOk()
            ->assertJson(['ok' => true, 'updated_existing' => true]);

        $this->assertSame(1, Inscripcion::where('actividad_id', $actividad->id)->where('user_id', $user->id)->count());
    }

    public function test_finalizar_pago_no_bloquea_a_otro_usuario_por_inscripcion_guest_ajena(): void
    {
        $actividad = $this->actividad();
        // Inscripción guest existente: su user_id es el owner compartido guest@milarepa.local.
        $this->inscribir($actividad, null, $this->guest('otra.persona@example.com'));

        $user = User::factory()->create();

        $this->actingAs($user)->withSession(['grid_pago' => [
            'actividad_id' => $actividad->id,
            'user_id' => $user->id,
            'guest' => null,
            'comprobante_path' => null,
            'pago_metodo' => null,
        ]])->postJson('/grid-actividades/pago/finalizar', [
            'pago_metodo' => 'efectivo',
            'invitados' => [],
        ])->assertOk();

        $this->assertSame(1, Inscripcion::where('actividad_id', $actividad->id)->where('user_id', $user->id)->count());
    }

    // --- pago() ------------------------------------------------------------

    public function test_pago_redirige_a_grilla_si_autenticado_ya_inscripto(): void
    {
        $actividad = $this->actividad();
        $user = User::factory()->create();
        $this->inscribir($actividad, $user);

        $this->actingAs($user)
            ->get("/grid-actividades/pago/{$actividad->id}")
            ->assertRedirect(route('grid-actividades.index'))
            ->assertSessionHas('error');
    }

    public function test_pago_no_redirige_en_flujo_update(): void
    {
        $actividad = $this->actividad();
        $user = User::factory()->create();
        $inscripcion = $this->inscribir($actividad, $user);

        $this->actingAs($user)->withSession(['grid_pago' => [
            'actividad_id' => $actividad->id,
            'user_id' => $user->id,
            'guest' => null,
            'inscripcion_id' => $inscripcion->id,
            'comprobante_path' => null,
            'pago_metodo' => null,
        ]])->get("/grid-actividades/pago/{$actividad->id}")
            ->assertOk();
    }
}

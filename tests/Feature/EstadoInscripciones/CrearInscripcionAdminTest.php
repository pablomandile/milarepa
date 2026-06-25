<?php

namespace Tests\Feature\EstadoInscripciones;

use App\Models\Actividad;
use App\Models\EsquemaPrecio;
use App\Models\EsquemaPrecioMembresia;
use App\Models\GuestUser;
use App\Models\Inscripcion;
use App\Models\Membresia;
use App\Models\Modalidad;
use App\Models\TipoActividad;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Crear inscripción iniciada por el admin (recepción inscribe en nombre de otra persona).
 * El admin prepara la sesión (grid_pago) con un usuario existente o un guest nuevo, y luego la
 * pantalla de pago existente (finalizarPago) crea la inscripción a nombre del participante destino,
 * no del admin.
 */
class CrearInscripcionAdminTest extends TestCase
{
    use DatabaseTransactions;

    private function asegurarGeografia(): void
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);
        DB::statement('INSERT IGNORE INTO provincias (id, nombre, pais_id) VALUES (1, ?, 1)', ['Buenos Aires']);
    }

    private function actividad(): Actividad
    {
        $this->asegurarGeografia();

        $general = Membresia::create(['nombre' => 'Sin membresía', 'valor' => 0]);
        $esquema = EsquemaPrecio::create(['nombre' => 'Esquema Crear Admin']);
        EsquemaPrecioMembresia::create([
            'esquema_precio_id' => $esquema->id,
            'membresia_id' => $general->id,
            'moneda_id' => DB::table('monedas')->value('id'),
            'precio' => 10000,
        ]);

        return Actividad::create([
            'nombre' => 'Evento Crear Admin',
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo CA'])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Presencial CA'])->id,
            'esquema_precio_id' => $esquema->id,
            'entidad_id' => DB::table('entidades')->value('id'),
            'fecha_inicio' => '2026-09-01 10:00:00',
            'fecha_fin' => '2026-09-02 20:00:00',
            'estado' => true,
        ]);
    }

    private function usuario(string $sufijo, ?string $rol = null): User
    {
        // factory() deja email_verified_at (necesario para el middleware 'verified').
        $user = User::factory()->create([
            'name' => 'Persona ' . $sufijo,
            'email' => "crear.admin.{$sufijo}@example.com",
        ]);
        if ($rol) {
            $user->assignRole($rol);
        }

        return $user;
    }

    private function guestData(): array
    {
        return [
            'name' => 'Nueva Persona',
            'email' => 'nueva.persona.ca@example.com',
            'telefono' => null,
            'whatsapp' => null,
            'pais_id' => 1,
            'provincia_id' => 1,
            'municipio_id' => null,
            'barrio_id' => null,
            'direccion' => null,
            'msgxmail' => false,
            'msgxwapp' => false,
            'accesibilidad' => false,
            'accesibilidad_desc' => null,
            'info_tarjetas_kadampa' => false,
            'registrar_datos' => false,
        ];
    }

    public function test_prepare_existente_guarda_user_en_sesion(): void
    {
        $actividad = $this->actividad();
        $admin = $this->usuario('admin1', 'admin');
        $target = $this->usuario('target1');

        $resp = $this->actingAs($admin)->postJson(route('estadoinscripciones.crear-prepare'), [
            'actividad_id' => $actividad->id,
            'modo' => 'existente',
            'user_id' => $target->id,
        ]);

        $resp->assertOk()->assertJson(['ok' => true]);
        $this->assertSame($target->id, session('grid_pago')['user_id']);
        $this->assertNull(session('grid_pago')['guest']);
    }

    public function test_prepare_nuevo_guarda_guest_en_sesion(): void
    {
        $actividad = $this->actividad();
        $admin = $this->usuario('admin2', 'admin');

        $resp = $this->actingAs($admin)->postJson(route('estadoinscripciones.crear-prepare'), [
            'actividad_id' => $actividad->id,
            'modo' => 'nuevo',
            'guest' => $this->guestData(),
        ]);

        $resp->assertOk();
        $this->assertNull(session('grid_pago')['user_id']);
        $this->assertSame('Nueva Persona', session('grid_pago')['guest']['name']);
    }

    public function test_prepare_requiere_rol_admin(): void
    {
        $actividad = $this->actividad();
        $sinRol = $this->usuario('sinrol');

        $this->actingAs($sinRol)->postJson(route('estadoinscripciones.crear-prepare'), [
            'actividad_id' => $actividad->id,
            'modo' => 'existente',
            'user_id' => $sinRol->id,
        ])->assertForbidden();
    }

    public function test_prepare_existente_exige_user_id(): void
    {
        $actividad = $this->actividad();
        $admin = $this->usuario('admin3', 'admin');

        $this->actingAs($admin)->postJson(route('estadoinscripciones.crear-prepare'), [
            'actividad_id' => $actividad->id,
            'modo' => 'existente',
        ])->assertStatus(422);
    }

    public function test_finaliza_a_nombre_del_usuario_destino_no_del_admin(): void
    {
        $actividad = $this->actividad();
        $admin = $this->usuario('admin4', 'admin');
        $target = $this->usuario('target4');

        // Admin prepara para el usuario destino…
        $this->actingAs($admin)->postJson(route('estadoinscripciones.crear-prepare'), [
            'actividad_id' => $actividad->id,
            'modo' => 'existente',
            'user_id' => $target->id,
        ])->assertOk();

        // …y finaliza (sigue logueado como admin). La inscripción es del destino, no del admin.
        $this->actingAs($admin)
            ->withSession(['grid_pago' => [
                'actividad_id' => $actividad->id,
                'user_id' => $target->id,
                'guest' => null,
                'comprobante_path' => null,
                'pago_metodo' => null,
            ]])
            ->postJson('/grid-actividades/pago/finalizar', ['pago_metodo' => 'efectivo', 'invitados' => []])
            ->assertOk();

        $inscripcion = Inscripcion::where('actividad_id', $actividad->id)->firstOrFail();
        $this->assertSame($target->id, $inscripcion->user_id);
        $this->assertNotSame($admin->id, $inscripcion->user_id);
    }

    public function test_finaliza_modo_nuevo_crea_guest(): void
    {
        $actividad = $this->actividad();
        $admin = $this->usuario('admin5', 'admin');

        $this->actingAs($admin)
            ->withSession(['grid_pago' => [
                'actividad_id' => $actividad->id,
                'user_id' => null,
                'guest' => $this->guestData(),
                'comprobante_path' => null,
                'pago_metodo' => null,
            ]])
            ->postJson('/grid-actividades/pago/finalizar', ['pago_metodo' => 'efectivo', 'invitados' => []])
            ->assertOk();

        $guest = GuestUser::where('email', 'nueva.persona.ca@example.com')->firstOrFail();
        $inscripcion = Inscripcion::where('actividad_id', $actividad->id)->firstOrFail();
        $this->assertSame($guest->id, $inscripcion->guest_user_id);
    }

    public function test_buscar_usuarios_filtra_y_requiere_rol(): void
    {
        $admin = $this->usuario('admin6', 'admin');
        $this->usuario('buscable-zzz'); // name "Persona buscable-zzz"

        // Sin rol → 403.
        $sinRol = $this->usuario('sinrol6');
        $this->actingAs($sinRol)->getJson(route('estadoinscripciones.buscar-usuarios', ['q' => 'buscable']))
            ->assertForbidden();

        // Con rol → encuentra por fragmento de nombre.
        $resp = $this->actingAs($admin)->getJson(route('estadoinscripciones.buscar-usuarios', ['q' => 'buscable']));
        $resp->assertOk();
        $this->assertNotEmpty($resp->json('usuarios'));
        $this->assertStringContainsString('buscable', strtolower($resp->json('usuarios.0.name')));
    }
}

<?php

namespace Tests\Feature\Hospedaje;

use App\Models\Actividad;
use App\Models\EsquemaPrecio;
use App\Models\EsquemaPrecioMembresia;
use App\Models\Hospedaje;
use App\Models\Inscripcion;
use App\Models\Invitado;
use App\Models\Membresia;
use App\Models\Modalidad;
use App\Models\TipoActividad;
use App\Models\User;
use App\Services\HospedajeCupoService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Cupo de acomodaciones de hospedaje por actividad: reserva al confirmar, libera al
 * borrar/editar, rechaza sobreventa. Disponibilidad por conteo de reservas activas.
 */
class CupoHospedajeTest extends TestCase
{
    use DatabaseTransactions;

    private function asegurarPaisDefault(): void
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);
    }

    /** @return array{0: Actividad, 1: Hospedaje} */
    private function escenario(?int $cantidad): array
    {
        $this->asegurarPaisDefault();

        $general = Membresia::create(['nombre' => 'Sin membresía', 'valor' => 0]);
        $esquema = EsquemaPrecio::create(['nombre' => 'Esquema Cupo Test']);
        EsquemaPrecioMembresia::create([
            'esquema_precio_id' => $esquema->id,
            'membresia_id' => $general->id,
            'moneda_id' => DB::table('monedas')->value('id'),
            'precio' => 10000,
        ]);

        $actividad = Actividad::create([
            'nombre' => 'Evento Cupo Test',
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo Cupo'])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Presencial Cupo'])->id,
            'esquema_precio_id' => $esquema->id,
            'entidad_id' => DB::table('entidades')->value('id'),
            'fecha_inicio' => '2026-08-01 10:00:00',
            'fecha_fin' => '2026-08-02 20:00:00',
            'estado' => true,
        ]);

        $hospedaje = Hospedaje::create(['nombre' => 'Habitación Doble', 'precio' => 3000]);
        $actividad->hospedajes()->attach($hospedaje->id, ['cantidad' => $cantidad]);

        return [$actividad, $hospedaje];
    }

    private function usuario(string $sufijo): User
    {
        return User::create([
            'name' => 'Insc ' . $sufijo,
            'email' => "insc.cupo.{$sufijo}@example.com",
            'password' => Hash::make('x'),
        ]);
    }

    private function finalizar(Actividad $actividad, User $user, array $hospedajesIds, array $invitados = [])
    {
        return $this->withSession(['grid_pago' => [
            'actividad_id' => $actividad->id,
            'user_id' => $user->id,
            'guest' => null,
            'comprobante_path' => null,
            'pago_metodo' => null,
        ]])->postJson('/grid-actividades/pago/finalizar', [
            'pago_metodo' => 'efectivo',
            'hospedajes_ids' => $hospedajesIds,
            'invitados' => $invitados,
        ]);
    }

    public function test_segunda_reserva_sin_cupo_es_rechazada(): void
    {
        [$actividad, $hospedaje] = $this->escenario(1);

        $this->finalizar($actividad, $this->usuario('a'), [$hospedaje->id])->assertOk();
        $resp = $this->finalizar($actividad, $this->usuario('b'), [$hospedaje->id]);

        $resp->assertStatus(422);
        $this->assertSame(1, Inscripcion::where('actividad_id', $actividad->id)->count());
    }

    public function test_borrar_inscripcion_libera_el_cupo(): void
    {
        [$actividad, $hospedaje] = $this->escenario(1);

        $r1 = $this->finalizar($actividad, $this->usuario('a'), [$hospedaje->id]);
        $r1->assertOk();
        Inscripcion::findOrFail($r1->json('inscripcion_id'))->delete();

        // Tras liberar, otra persona puede tomar la acomodación.
        $this->finalizar($actividad, $this->usuario('b'), [$hospedaje->id])->assertOk();
    }

    public function test_cantidad_null_es_ilimitada(): void
    {
        [$actividad, $hospedaje] = $this->escenario(null);

        $this->finalizar($actividad, $this->usuario('a'), [$hospedaje->id])->assertOk();
        $this->finalizar($actividad, $this->usuario('b'), [$hospedaje->id])->assertOk();
        $this->assertSame(2, Inscripcion::where('actividad_id', $actividad->id)->count());
    }

    public function test_invitado_consume_cupo(): void
    {
        [$actividad, $hospedaje] = $this->escenario(1);

        // Primer inscripto: un invitado toma la única unidad.
        $this->finalizar($actividad, $this->usuario('a'), [], [
            ['nombre' => 'Inv', 'apellido' => 'Uno', 'hospedajes_ids' => [$hospedaje->id]],
        ])->assertOk();

        // Segundo inscripto (titular) ya no tiene cupo.
        $this->finalizar($actividad, $this->usuario('b'), [$hospedaje->id])->assertStatus(422);
    }

    public function test_titular_mas_invitado_sobre_misma_acomodacion_respeta_cupo(): void
    {
        [$actividad, $hospedaje] = $this->escenario(1);

        // Una sola inscripción pide 2 unidades (titular + invitado) de cupo 1 → rechazo.
        $this->finalizar($actividad, $this->usuario('a'), [$hospedaje->id], [
            ['nombre' => 'Inv', 'apellido' => 'Uno', 'hospedajes_ids' => [$hospedaje->id]],
        ])->assertStatus(422);

        $this->assertSame(0, Inscripcion::where('actividad_id', $actividad->id)->count());
    }

    public function test_disponibles_descuenta_reservas_y_excluye_la_propia_inscripcion(): void
    {
        [$actividad, $hospedaje] = $this->escenario(1);
        $user = $this->usuario('a');

        $inscripcion = Inscripcion::create([
            'actividad_id' => $actividad->id,
            'user_id' => $user->id,
            'membresia' => 'Sin membresía',
            'precioGeneral' => 10000,
            'montoapagar' => 13000,
            'pago' => 'Pendiente',
            'estado' => 'Registrada',
            'envioLinkStream' => 'No aplica',
            'asistencia' => 'Pendiente',
            'online' => false,
            'hospedaje_id' => $hospedaje->id,
        ]);

        $cupo = app(HospedajeCupoService::class);
        // Sin excluir: la unidad está tomada → 0.
        $this->assertSame(0, $cupo->disponibles($actividad)[$hospedaje->id]);
        // Excluyendo la propia inscripción (caso edición): vuelve a haber 1.
        $this->assertSame(1, $cupo->disponibles($actividad, $inscripcion->id)[$hospedaje->id]);
    }
}

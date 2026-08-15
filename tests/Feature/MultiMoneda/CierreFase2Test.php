<?php

namespace Tests\Feature\MultiMoneda;

use App\Models\Actividad;
use App\Models\BotonPago;
use App\Models\EsquemaPrecio;
use App\Models\EsquemaPrecioMembresia;
use App\Models\Inscripcion;
use App\Models\Membresia;
use App\Models\Modalidad;
use App\Models\Moneda;
use App\Models\TipoActividad;
use App\Models\User;
use App\Services\CobroService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Cierre de la fase 2: la deuda de una inscripción dividida son sus DOS
 * porciones, y el botón de pago de la actividad acompaña a la moneda elegida.
 */
class CierreFase2Test extends TestCase
{
    use DatabaseTransactions;

    private Moneda $principal;
    private Moneda $secundaria;
    private BotonPago $botonPesos;
    private BotonPago $botonDolares;

    private function monedas(): void
    {
        $this->principal = Moneda::principal()
            ?? Moneda::create(['nombre' => 'Pesos Test', 'simbolo' => '$', 'es_principal' => true]);
        $this->secundaria = Moneda::create(['nombre' => 'Dolar Test ' . uniqid(), 'simbolo' => 'U$T']);
    }

    /** Actividad con "sin membresía" en dos monedas, cada una con su botón de pago. */
    private function actividadConBotones(): Actividad
    {
        $this->monedas();

        $this->botonPesos = BotonPago::create(['nombre' => 'Transferencia ARS', 'link' => 'https://pago.test/ars']);
        $this->botonDolares = BotonPago::create(['nombre' => 'Transferencia USD', 'link' => 'https://pago.test/usd']);

        $general = Membresia::create(['nombre' => 'Sin membresía', 'valor' => 0]);
        $esquema = EsquemaPrecio::create(['nombre' => 'Esquema Botones ' . uniqid()]);
        EsquemaPrecioMembresia::create([
            'esquema_precio_id' => $esquema->id,
            'membresia_id' => $general->id,
            'moneda_id' => $this->principal->id,
            'precio' => 10000,
            'botonpago_id' => $this->botonPesos->id,
        ]);
        EsquemaPrecioMembresia::create([
            'esquema_precio_id' => $esquema->id,
            'membresia_id' => $general->id,
            'moneda_id' => $this->secundaria->id,
            'precio' => 100,
            'botonpago_id' => $this->botonDolares->id,
        ]);

        return Actividad::create([
            'nombre' => 'Evento Botones ' . uniqid(),
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo Bot ' . uniqid()])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Presencial Bot ' . uniqid()])->id,
            'esquema_precio_id' => $esquema->id,
            'entidad_id' => DB::table('entidades')->value('id'),
            'fecha_inicio' => '2027-01-10 10:00:00',
            'fecha_fin' => '2027-01-11 20:00:00',
            'estado' => true,
        ]);
    }

    private function inscripcion(Actividad $actividad, ?int $monedaId, float $monto, ?float $porcionPrincipal = null): Inscripcion
    {
        $user = User::create([
            'name' => 'Cierre F2',
            'email' => 'cierre.f2' . uniqid() . '@example.com',
            'password' => Hash::make('x'),
        ]);

        return Inscripcion::create([
            'actividad_id' => $actividad->id,
            'user_id' => $user->id,
            'membresia' => 'Sin membresía',
            'precioGeneral' => $monto,
            'montoActividad' => $monto,
            'montoapagar' => $monto,
            'moneda_id' => $monedaId,
            'monto_moneda_principal' => $porcionPrincipal,
            'pago' => 'Pendiente',
            'estado' => 'Registrada',
            'envioLinkStream' => 'No aplica',
            'asistencia' => 'Pendiente',
            'online' => false,
        ]);
    }

    public function test_la_deuda_incluye_la_porcion_en_la_moneda_principal(): void
    {
        $actividad = $this->actividadConBotones();
        $inscripcion = $this->inscripcion($actividad, $this->secundaria->id, 120, 2000);

        $this->assertSame(2120.0, $inscripcion->totalAdeudado());
    }

    public function test_cobrar_solo_la_porcion_en_moneda_no_salda_la_inscripcion(): void
    {
        $actividad = $this->actividadConBotones();
        $inscripcion = $this->inscripcion($actividad, $this->secundaria->id, 120, 2000);

        // Paga los 120 en dólares: quedan los 2.000 en pesos de los servicios.
        app(CobroService::class)->registrar($inscripcion, ['monto' => 120, 'fecha_pago' => '2026-08-15']);

        $inscripcion->refresh();
        $this->assertSame('Parcial', $inscripcion->pago);
        $this->assertSame(2000.0, $inscripcion->saldoPendiente());
    }

    public function test_cobrar_ambas_porciones_salda(): void
    {
        $actividad = $this->actividadConBotones();
        $inscripcion = $this->inscripcion($actividad, $this->secundaria->id, 120, 2000);

        app(CobroService::class)->registrar($inscripcion, ['monto' => 2120, 'fecha_pago' => '2026-08-15']);

        $inscripcion->refresh();
        $this->assertSame('Saldado', $inscripcion->pago);
        $this->assertSame('Confirmada', $inscripcion->estado);
    }

    public function test_una_inscripcion_en_pesos_sigue_saldando_igual(): void
    {
        $actividad = $this->actividadConBotones();
        $inscripcion = $this->inscripcion($actividad, null, 10000);

        app(CobroService::class)->registrar($inscripcion, ['monto' => 10000, 'fecha_pago' => '2026-08-15']);

        $inscripcion->refresh();
        $this->assertSame('Saldado', $inscripcion->pago);
    }

    public function test_el_checkout_emite_un_boton_de_pago_por_moneda(): void
    {
        $actividad = $this->actividadConBotones();

        $respuesta = $this->withSession(['grid_pago' => [
            'actividad_id' => $actividad->id,
            'user_id' => null,
            'guest' => ['name' => 'Invitado F2', 'email' => 'invitado.f2' . uniqid() . '@example.com'],
        ]])->get(route('grid-actividades.pago', $actividad->id));

        $respuesta->assertOk();
        $html = $respuesta->getContent();

        // Los dos links tienen que viajar para que el selector pueda cambiarlos.
        // (van dentro del data-page, o sea con las barras escapadas por JSON).
        $this->assertStringContainsString('botonesPagoPorMoneda', $html);
        $this->assertStringContainsString('pago.test\/ars', $html);
        $this->assertStringContainsString('pago.test\/usd', $html);
    }
}

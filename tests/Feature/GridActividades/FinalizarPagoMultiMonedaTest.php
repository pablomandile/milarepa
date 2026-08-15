<?php

namespace Tests\Feature\GridActividades;

use App\Models\Actividad;
use App\Models\Comida;
use App\Models\EsquemaPrecio;
use App\Models\EsquemaPrecioMembresia;
use App\Models\Grabacion;
use App\Models\Hospedaje;
use App\Models\Inscripcion;
use App\Models\LugarHospedaje;
use App\Models\Membresia;
use App\Models\MetodoPago;
use App\Models\Modalidad;
use App\Models\Moneda;
use App\Models\TipoActividad;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Checkout público multi-moneda (total dividido): montoapagar es la porción en
 * la moneda elegida; los servicios sin precio en ella se cobran en la moneda
 * principal (monto_moneda_principal). Mercado Pago solo acepta la principal.
 */
class FinalizarPagoMultiMonedaTest extends TestCase
{
    use DatabaseTransactions;

    private Moneda $principal;
    private Moneda $secundaria;
    private Comida $comidaConSecundaria;   // plana 1000, secundaria 10
    private Comida $comidaSoloPrincipal;   // plana 2000, sin fila secundaria
    private Grabacion $grabacion;          // plana 500, secundaria 5

    private function monedaPrincipal(): Moneda
    {
        return Moneda::principal()
            ?? Moneda::create(['nombre' => 'Pesos Test', 'simbolo' => '$', 'es_principal' => true]);
    }

    /** Actividad con esquema en dos monedas (general 10000 principal / 100 secundaria). */
    private function actividadMultiMoneda(): Actividad
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);
        DB::statement('INSERT IGNORE INTO provincias (id, nombre) VALUES (1, ?)', ['Buenos Aires']);

        $this->principal = $this->monedaPrincipal();
        $this->secundaria = Moneda::create(['nombre' => 'Dolar Test ' . uniqid(), 'simbolo' => 'U$T']);

        $general = Membresia::create(['nombre' => 'Sin membresía', 'valor' => 0]);
        $esquema = EsquemaPrecio::create(['nombre' => 'Esquema MultiMoneda']);
        EsquemaPrecioMembresia::create([
            'esquema_precio_id' => $esquema->id,
            'membresia_id' => $general->id,
            'moneda_id' => $this->principal->id,
            'precio' => 10000,
        ]);
        EsquemaPrecioMembresia::create([
            'esquema_precio_id' => $esquema->id,
            'membresia_id' => $general->id,
            'moneda_id' => $this->secundaria->id,
            'precio' => 100,
        ]);

        $this->grabacion = Grabacion::create(['nombre' => 'Grabacion MM', 'valor' => 500]);
        $this->grabacion->precios()->create(['moneda_id' => $this->secundaria->id, 'precio' => 5]);

        $actividad = Actividad::create([
            'nombre' => 'Evento MultiMoneda',
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo MM'])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Presencial MM'])->id,
            'esquema_precio_id' => $esquema->id,
            'grabacion_id' => $this->grabacion->id,
            'entidad_id' => DB::table('entidades')->value('id'),
            'fecha_inicio' => '2026-10-01 10:00:00',
            'fecha_fin' => '2026-10-02 20:00:00',
            'estado' => true,
        ]);

        $this->comidaConSecundaria = Comida::create(['nombre' => 'Comida MM USD', 'descripcion' => 'x', 'precio' => 1000, 'vegano' => false, 'celiaco' => false]);
        $this->comidaConSecundaria->precios()->create(['moneda_id' => $this->secundaria->id, 'precio' => 10]);
        $this->comidaSoloPrincipal = Comida::create(['nombre' => 'Comida MM Pesos', 'descripcion' => 'x', 'precio' => 2000, 'vegano' => false, 'celiaco' => false]);
        $actividad->comidas()->attach([$this->comidaConSecundaria->id, $this->comidaSoloPrincipal->id]);

        return $actividad;
    }

    private function sesionGuest(Actividad $actividad): array
    {
        return ['grid_pago' => [
            'actividad_id' => $actividad->id,
            'user_id' => null,
            'guest' => [
                'name' => 'Guest MultiMoneda',
                'email' => 'multimoneda' . uniqid() . '@example.com',
                'pais_id' => 1,
                'provincia_id' => 1,
                'registrar_datos' => false,
            ],
            'comprobante_path' => null,
            'pago_metodo' => null,
        ]];
    }

    public function test_finalizar_en_moneda_secundaria_divide_el_total(): void
    {
        $actividad = $this->actividadMultiMoneda();

        $this->withSession($this->sesionGuest($actividad))->postJson('/grid-actividades/pago/finalizar', [
            'pago_metodo' => 'efectivo',
            'moneda_id' => $this->secundaria->id,
            'incluye_grabacion' => true,
            'comidas_ids' => [$this->comidaConSecundaria->id, $this->comidaSoloPrincipal->id],
            'invitados' => [],
        ])->assertOk()->assertJson(['ok' => true]);

        $inscripcion = Inscripcion::where('actividad_id', $actividad->id)->latest('id')->firstOrFail();
        // Porción en la moneda elegida: actividad 100 + grabación 5 + comida 10.
        $this->assertSame(115.0, (float) $inscripcion->montoapagar);
        $this->assertSame(10.0, (float) $inscripcion->montoComidas);
        $this->assertSame(5.0, (float) $inscripcion->montoGrabacion);
        // Porción en la principal: la comida sin precio en la moneda elegida.
        $this->assertSame(2000.0, (float) $inscripcion->monto_moneda_principal);
        $this->assertSame((int) $this->secundaria->id, (int) $inscripcion->moneda_id);
    }

    public function test_finalizar_sin_moneda_usa_precios_planos_y_persiste_la_principal(): void
    {
        $actividad = $this->actividadMultiMoneda();

        $this->withSession($this->sesionGuest($actividad))->postJson('/grid-actividades/pago/finalizar', [
            'pago_metodo' => 'efectivo',
            'incluye_grabacion' => true,
            'comidas_ids' => [$this->comidaConSecundaria->id, $this->comidaSoloPrincipal->id],
            'invitados' => [],
        ])->assertOk();

        $inscripcion = Inscripcion::where('actividad_id', $actividad->id)->latest('id')->firstOrFail();
        // 10000 actividad + 500 grabación + 1000 + 2000 comidas.
        $this->assertSame(13500.0, (float) $inscripcion->montoapagar);
        $this->assertSame(3000.0, (float) $inscripcion->montoComidas);
        $this->assertNull($inscripcion->monto_moneda_principal);
        $this->assertSame((int) $this->principal->id, (int) $inscripcion->moneda_id);
    }

    public function test_finalizar_persiste_monto_hospedaje_del_titular(): void
    {
        $actividad = $this->actividadMultiMoneda();
        $hospedaje = Hospedaje::create([
            'nombre' => 'Hospedaje MM',
            'precio' => 3000,
            'lugar_hospedaje_id' => LugarHospedaje::create(['nombre' => 'Lugar MM', 'direccion' => 'Calle Falsa 123'])->id,
        ]);
        $actividad->hospedajes()->attach($hospedaje->id);

        $this->withSession($this->sesionGuest($actividad))->postJson('/grid-actividades/pago/finalizar', [
            'pago_metodo' => 'efectivo',
            'hospedajes_ids' => [$hospedaje->id],
            'invitados' => [],
        ])->assertOk();

        $inscripcion = Inscripcion::where('actividad_id', $actividad->id)->latest('id')->firstOrFail();
        $this->assertSame(3000.0, (float) $inscripcion->montoHospedaje);
        $this->assertSame(13000.0, (float) $inscripcion->montoapagar);
    }

    public function test_mercadopago_en_moneda_secundaria_devuelve_422(): void
    {
        $actividad = $this->actividadMultiMoneda();
        $mp = MetodoPago::firstOrCreate(['nombre' => 'Mercado Pago']);
        $actividad->metodosPago()->attach($mp->id);

        $this->withSession($this->sesionGuest($actividad))->postJson('/grid-actividades/pago/finalizar', [
            'pago_metodo' => 'mercadopago',
            'moneda_id' => $this->secundaria->id,
            'invitados' => [],
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Mercado Pago solo está disponible para pagos en pesos.');

        $this->assertSame(0, Inscripcion::where('actividad_id', $actividad->id)->count());
    }

    public function test_moneda_fuera_del_esquema_devuelve_422(): void
    {
        $actividad = $this->actividadMultiMoneda();
        $ajena = Moneda::create(['nombre' => 'Libra Test ' . uniqid(), 'simbolo' => 'LT']);

        $this->withSession($this->sesionGuest($actividad))->postJson('/grid-actividades/pago/finalizar', [
            'pago_metodo' => 'efectivo',
            'moneda_id' => $ajena->id,
            'invitados' => [],
        ])->assertStatus(422)
            ->assertJsonPath('message', 'La moneda seleccionada no está disponible para esta actividad.');
    }

    public function test_flujo_update_recalcula_en_la_moneda_original(): void
    {
        $actividad = $this->actividadMultiMoneda();
        $user = User::factory()->create();
        $inscripcion = Inscripcion::create([
            'actividad_id' => $actividad->id,
            'user_id' => $user->id,
            'membresia' => 'Sin membresía',
            'precioGeneral' => 100,
            'montoActividad' => 100,
            'montoapagar' => 100,
            'moneda_id' => $this->secundaria->id,
            'pago' => 'Pendiente',
            'estado' => 'Registrada',
            'envioLinkStream' => 'No aplica',
            'asistencia' => 'Pendiente',
            'online' => false,
        ]);

        // "Pagar" desde Mis inscripciones: la sesión trae inscripcion_id y el
        // POST no manda moneda (el selector viaja deshabilitado).
        $this->actingAs($user)->withSession(['grid_pago' => [
            'actividad_id' => $actividad->id,
            'user_id' => $user->id,
            'guest' => null,
            'inscripcion_id' => $inscripcion->id,
            'comprobante_path' => null,
            'pago_metodo' => null,
        ]])->postJson('/grid-actividades/pago/finalizar', [
            'pago_metodo' => 'efectivo',
            'comidas_ids' => [$this->comidaConSecundaria->id, $this->comidaSoloPrincipal->id],
            'invitados' => [],
        ])->assertOk()->assertJson(['updated_existing' => true]);

        $inscripcion->refresh();
        $this->assertSame((int) $this->secundaria->id, (int) $inscripcion->moneda_id);
        // 100 actividad + 10 comida en secundaria; la otra comida va a la principal.
        $this->assertSame(110.0, (float) $inscripcion->montoapagar);
        $this->assertSame(2000.0, (float) $inscripcion->monto_moneda_principal);
    }

    public function test_invitados_heredan_moneda_y_total_dividido(): void
    {
        $actividad = $this->actividadMultiMoneda();

        $this->withSession($this->sesionGuest($actividad))->postJson('/grid-actividades/pago/finalizar', [
            'pago_metodo' => 'efectivo',
            'moneda_id' => $this->secundaria->id,
            'invitados' => [[
                'nombre' => 'Invitada',
                'apellido' => 'MultiMoneda',
                'comidas_ids' => [$this->comidaConSecundaria->id, $this->comidaSoloPrincipal->id],
            ]],
        ])->assertOk();

        $inscripcion = Inscripcion::where('actividad_id', $actividad->id)->latest('id')->firstOrFail();
        $invitado = $inscripcion->invitados()->firstOrFail();

        $this->assertSame((int) $this->secundaria->id, (int) $invitado->moneda_id);
        // Invitado: precio general 100 + comida 10 en secundaria.
        $this->assertSame(110.0, (float) $invitado->montoapagar);
        $this->assertSame(2000.0, (float) $invitado->monto_moneda_principal);
        // Titular 100 + invitado 110 en secundaria; 2000 del invitado en principal.
        $this->assertSame(210.0, (float) $inscripcion->montoapagar);
        $this->assertSame(2000.0, (float) $inscripcion->monto_moneda_principal);
    }

    public function test_no_es_gratis_si_solo_queda_porcion_en_principal(): void
    {
        $actividad = $this->actividadMultiMoneda();
        // Esquema con precio 0 en la secundaria para aislar la porción principal.
        EsquemaPrecioMembresia::where('esquema_precio_id', $actividad->esquema_precio_id)
            ->where('moneda_id', $this->secundaria->id)
            ->update(['precio' => 0]);

        $this->withSession($this->sesionGuest($actividad))->postJson('/grid-actividades/pago/finalizar', [
            'pago_metodo' => 'efectivo',
            'moneda_id' => $this->secundaria->id,
            'comidas_ids' => [$this->comidaSoloPrincipal->id],
            'invitados' => [],
        ])->assertOk();

        $inscripcion = Inscripcion::where('actividad_id', $actividad->id)->latest('id')->firstOrFail();
        $this->assertSame(0.0, (float) $inscripcion->montoapagar);
        $this->assertSame(2000.0, (float) $inscripcion->monto_moneda_principal);
        // Hay plata pendiente en la principal: NO debe quedar como saldada.
        $this->assertSame('Pendiente', $inscripcion->pago);
        $this->assertSame('Registrada', $inscripcion->estado);
    }
}

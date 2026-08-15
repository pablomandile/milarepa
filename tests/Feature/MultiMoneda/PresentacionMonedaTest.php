<?php

namespace Tests\Feature\MultiMoneda;

use App\Mail\InscripcionConfirmada;
use App\Models\Actividad;
use App\Models\EsquemaPrecio;
use App\Models\EsquemaPrecioMembresia;
use App\Models\Inscripcion;
use App\Models\Membresia;
use App\Models\Modalidad;
use App\Models\Moneda;
use App\Models\TipoActividad;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Cómo se MUESTRA la plata cuando hay varias monedas: la grilla pública tiene
 * que mandar la moneda de cada línea del esquema (si no, las cards pintan un
 * precio en dólares con el símbolo de pesos) y los mails tienen que usar el
 * símbolo de la inscripción más la porción en pesos del total dividido.
 */
class PresentacionMonedaTest extends TestCase
{
    use DatabaseTransactions;

    private Moneda $principal;
    private Moneda $secundaria;

    private function monedas(): void
    {
        $this->principal = Moneda::principal()
            ?? Moneda::create(['nombre' => 'Pesos Test', 'simbolo' => '$', 'es_principal' => true]);
        $this->secundaria = Moneda::create(['nombre' => 'Dolar Test ' . uniqid(), 'simbolo' => 'U$T']);
    }

    /** Actividad "sin membresía" cargada en las dos monedas: 10000 y 100. */
    private function actividadDosMonedas(): Actividad
    {
        $this->monedas();

        $general = Membresia::create(['nombre' => 'Sin membresía', 'valor' => 0]);
        $esquema = EsquemaPrecio::create(['nombre' => 'Esquema Pres ' . uniqid()]);
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

        return Actividad::create([
            'nombre' => 'Evento Presentacion ' . uniqid(),
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo Pres ' . uniqid()])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Presencial Pres ' . uniqid()])->id,
            'esquema_precio_id' => $esquema->id,
            'entidad_id' => DB::table('entidades')->value('id'),
            'fecha_inicio' => '2026-12-20 10:00:00',
            'fecha_fin' => '2026-12-21 20:00:00',
            'estado' => true,
        ]);
    }

    public function test_la_grilla_publica_manda_la_moneda_de_cada_linea(): void
    {
        $actividad = $this->actividadDosMonedas();

        $html = $this->get(route('grid-actividades.index'))->assertOk()->getContent();

        // El símbolo tiene que viajar para que la card no invente pesos.
        $this->assertStringContainsString('U$T', $html);
        $this->assertStringContainsString('es_principal', $html);
    }

    public function test_la_grilla_publica_sigue_sin_emitir_precios_por_moneda_de_servicios(): void
    {
        $this->actividadDosMonedas();

        $html = $this->get(route('grid-actividades.index'))->assertOk()->getContent();

        // El accessor de servicios es opt-in: si alguien lo mete en $appends,
        // la grilla pública se llena de datos que no necesita.
        $this->assertStringNotContainsString('precios_por_moneda', $html);
    }

    private function inscripcion(Actividad $actividad, ?int $monedaId, float $monto, ?float $porcionPrincipal = null): Inscripcion
    {
        $user = User::create([
            'name' => 'Mail Moneda',
            'email' => 'mail.moneda' . uniqid() . '@example.com',
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

    public function test_el_mail_usa_el_simbolo_de_la_moneda_y_muestra_el_split(): void
    {
        $actividad = $this->actividadDosMonedas();
        $inscripcion = $this->inscripcion($actividad, $this->secundaria->id, 120, 2000);

        $html = (new InscripcionConfirmada($inscripcion, 'emails.inscripcion_registrada'))->render();

        $this->assertStringContainsString('U$T 120,00', $html);
        $this->assertStringContainsString('2.000,00', $html);
        $this->assertStringContainsString('no tienen precio en U$T', $html);
    }

    public function test_el_mail_de_una_inscripcion_en_pesos_no_cambia(): void
    {
        $actividad = $this->actividadDosMonedas();
        $inscripcion = $this->inscripcion($actividad, null, 15000);

        $html = (new InscripcionConfirmada($inscripcion, 'emails.inscripcion_registrada'))->render();

        $this->assertStringContainsString('15.000,00', $html);
        $this->assertStringNotContainsString('U$T', $html);
        $this->assertStringNotContainsString('no tienen precio en', $html);
    }
}

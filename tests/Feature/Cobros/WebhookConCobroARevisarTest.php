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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Un cobro `a_revisar` (comprobante informado) no interfiere con el pago online:
 * cuando entra el cobro aprobado de MP (confirmado + recalcular), la inscripción
 * queda Saldada y el a revisar sigue sin sumar. Es el mismo camino que recorre
 * MercadoPagoWebhookController al registrar el pago aprobado.
 */
class WebhookConCobroARevisarTest extends TestCase
{
    use DatabaseTransactions;

    private function inscripcion(float $monto = 10000): Inscripcion
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);

        $general = Membresia::create(['nombre' => 'Sin membresía', 'valor' => 0]);
        $esquema = EsquemaPrecio::create(['nombre' => 'Esquema Webhook AR']);
        EsquemaPrecioMembresia::create([
            'esquema_precio_id' => $esquema->id,
            'membresia_id' => $general->id,
            'moneda_id' => DB::table('monedas')->value('id'),
            'precio' => $monto,
        ]);

        $actividad = Actividad::create([
            'nombre' => 'Evento Webhook AR',
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo WAR'])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Presencial WAR'])->id,
            'esquema_precio_id' => $esquema->id,
            'entidad_id' => DB::table('entidades')->value('id'),
            'fecha_inicio' => '2026-09-01 10:00:00',
            'fecha_fin' => '2026-09-02 20:00:00',
            'estado' => true,
        ]);

        return Inscripcion::create([
            'actividad_id' => $actividad->id,
            'user_id' => User::factory()->create()->id,
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

    public function test_pago_mp_aprobado_salda_aunque_haya_cobro_a_revisar(): void
    {
        $inscripcion = $this->inscripcion(10000);
        $svc = app(CobroService::class);

        $imagen = Imagen::create(['nombre' => 'wh.jpg', 'ruta' => 'comprobantes/wh.jpg']);
        $svc->registrarComprobanteARevisar($inscripcion, $imagen->id, null, 'checkout');

        // Guarda de idempotencia del webhook: sigue Pendiente (el a revisar no suma),
        // así que el pago aprobado NO se frena.
        $inscripcion->refresh();
        $this->assertSame('Pendiente', $inscripcion->pago);

        // El webhook registra el cobro aprobado con recalcular (mismo camino que
        // MercadoPagoWebhookController).
        $svc->registrar($inscripcion, [
            'monto' => 10000,
            'fecha_pago' => now()->toDateString(),
            'referencia' => 'mp-123456',
            'origen' => 'mercadopago',
        ]);

        $inscripcion->refresh();
        $this->assertSame('Saldado', $inscripcion->pago);
        $this->assertSame('Confirmada', $inscripcion->estado);
        $this->assertEquals(10000.0, $inscripcion->montoCobrado());
        $this->assertEquals(0.0, $inscripcion->saldoPendiente());

        // Conviven el confirmado de MP y el a revisar remanente (que no suma).
        $this->assertSame(1, $inscripcion->cobros()->confirmados()->count());
        $this->assertSame(1, $inscripcion->cobros()->aRevisar()->count());
        $this->assertSame(Cobro::ESTADO_A_REVISAR, $inscripcion->cobrosARevisar()->first()->estado);
    }
}

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
 * Cuánto dice haber pagado quien sube el comprobante.
 *
 * Un cobro `a_revisar` nacía SIEMPRE con el saldo pendiente entero como monto, así
 * que una seña de $ 4.000 sobre una actividad de $ 10.000 se mostraba al admin como
 * "informado a revisar: $ 10.000" — el número justo que mira para decidir. Ahora el
 * importe declarado se respeta (flag `monto_declarado`) y el provisional se sigue
 * recalculando contra el saldo para todos los flujos que no lo mandan.
 */
class ComprobanteMontoInformadoTest extends TestCase
{
    use DatabaseTransactions;

    private function inscripcion(float $monto = 10000): Inscripcion
    {
        $general = Membresia::create(['nombre' => 'Sin membresía', 'valor' => 0]);
        $esquema = EsquemaPrecio::create(['nombre' => 'Esquema Informado ' . uniqid()]);
        EsquemaPrecioMembresia::create([
            'esquema_precio_id' => $esquema->id,
            'membresia_id' => $general->id,
            'moneda_id' => DB::table('monedas')->value('id'),
            'precio' => $monto,
        ]);

        $actividad = Actividad::create([
            'nombre' => 'Evento Informado ' . uniqid(),
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo INF ' . uniqid()])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Presencial INF ' . uniqid()])->id,
            'esquema_precio_id' => $esquema->id,
            'entidad_id' => DB::table('entidades')->value('id'),
            'fecha_inicio' => '2026-11-01 10:00:00',
            'fecha_fin' => '2026-11-02 20:00:00',
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

    private function imagen(string $nombre): Imagen
    {
        return Imagen::create(['nombre' => $nombre, 'ruta' => "comprobantes/{$nombre}-" . uniqid()]);
    }

    public function test_el_importe_informado_se_graba_tal_cual(): void
    {
        $inscripcion = $this->inscripcion(10000);

        $cobro = app(CobroService::class)->registrarComprobanteARevisar(
            $inscripcion,
            $this->imagen('sena.jpg')->id,
            null,
            'checkout',
            montoInformado: 4000
        );

        $this->assertSame(Cobro::ESTADO_A_REVISAR, $cobro->estado);
        $this->assertEquals(4000.0, (float) $cobro->monto); // NO los 10.000 del saldo
        $this->assertTrue($cobro->monto_declarado);

        // Sigue sin sumar al saldo: es plata informada, no verificada.
        $this->assertEquals(0.0, $inscripcion->fresh()->montoCobrado());
    }

    public function test_dos_senias_informadas_suman_sobre_un_solo_cobro(): void
    {
        $inscripcion = $this->inscripcion(10000);
        $svc = app(CobroService::class);

        $primera = $svc->registrarComprobanteARevisar($inscripcion, $this->imagen('una.jpg')->id, null, 'checkout', montoInformado: 4000);
        $segunda = $svc->registrarComprobanteARevisar($inscripcion, $this->imagen('dos.jpg')->id, null, 'checkout', montoInformado: 2500);

        // Invariante intacto: un solo a_revisar por (cobrable, moneda).
        $this->assertSame($primera->id, $segunda->id);
        $this->assertSame(1, $inscripcion->fresh()->cobrosARevisar()->count());

        $cobro = $segunda->fresh();
        $this->assertEquals(6500.0, (float) $cobro->monto);
        $this->assertSame(2, $cobro->comprobantes()->count());
    }

    /**
     * Una segunda subida sin declarar importe no puede pisar con el saldo entero lo
     * que la persona ya había informado.
     */
    public function test_una_subida_sin_importe_no_pisa_el_monto_declarado(): void
    {
        $inscripcion = $this->inscripcion(10000);
        $svc = app(CobroService::class);

        $svc->registrarComprobanteARevisar($inscripcion, $this->imagen('declarada.jpg')->id, null, 'checkout', montoInformado: 4000);
        $cobro = $svc->registrarComprobanteARevisar($inscripcion, $this->imagen('muda.jpg')->id, null, 'checkout');

        $this->assertEquals(4000.0, (float) $cobro->fresh()->monto);
        $this->assertTrue($cobro->fresh()->monto_declarado);
    }

    /** No-regresión: los flujos que no mandan importe siguen usando el saldo. */
    public function test_sin_importe_se_sigue_grabando_el_saldo_pendiente(): void
    {
        $inscripcion = $this->inscripcion(10000);

        $cobro = app(CobroService::class)
            ->registrarComprobanteARevisar($inscripcion, $this->imagen('clasica.jpg')->id, null, 'checkout');

        $this->assertEquals(10000.0, (float) $cobro->monto);
        $this->assertFalse($cobro->monto_declarado);
    }

    public function test_el_endpoint_de_subida_acepta_el_importe(): void
    {
        Storage::fake('public');

        $inscripcion = $this->inscripcion(10000);
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post("/inscripciones/{$inscripcion->id}/comprobante", [
                'comprobante' => UploadedFile::fake()->image('transf.jpg'),
                'monto_informado' => 4000,
            ])
            ->assertRedirect();

        $cobro = $inscripcion->fresh()->cobros()->sole();
        $this->assertEquals(4000.0, (float) $cobro->monto);
        $this->assertTrue($cobro->monto_declarado);
    }
}

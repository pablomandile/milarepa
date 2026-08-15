<?php

namespace Tests\Feature\Cobros;

use App\Models\Actividad;
use App\Models\Cobro;
use App\Models\EsquemaPrecio;
use App\Models\EstadoCuentaMembresia;
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
 * Los cobros guardan la moneda del cobrable sin que cada llamador tenga que
 * pasarla (webhook de MP, checkout, admin, POS). Sólo las inscripciones a
 * actividades son multi-moneda: clases y membresías van siempre en la principal.
 */
class CobroMonedaTest extends TestCase
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

    private function inscripcion(?int $monedaId, float $monto = 100): Inscripcion
    {
        $actividad = Actividad::create([
            'nombre' => 'Evento Cobro Moneda ' . uniqid(),
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo CM ' . uniqid()])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Presencial CM ' . uniqid()])->id,
            'esquema_precio_id' => EsquemaPrecio::create(['nombre' => 'Esquema CM ' . uniqid()])->id,
            'entidad_id' => DB::table('entidades')->value('id'),
            'fecha_inicio' => '2026-12-01 10:00:00',
            'fecha_fin' => '2026-12-02 20:00:00',
            'estado' => true,
        ]);

        $user = User::create([
            'name' => 'Cobro Moneda',
            'email' => 'cobro.moneda' . uniqid() . '@example.com',
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
            'pago' => 'Pendiente',
            'estado' => 'Registrada',
            'envioLinkStream' => 'No aplica',
            'asistencia' => 'Pendiente',
            'online' => false,
        ]);
    }

    public function test_cobro_hereda_la_moneda_de_la_inscripcion(): void
    {
        $this->monedas();
        $inscripcion = $this->inscripcion($this->secundaria->id, 120);

        $cobro = app(CobroService::class)->registrar($inscripcion, [
            'monto' => 120,
            'fecha_pago' => '2026-08-15',
        ]);

        $this->assertSame($this->secundaria->id, $cobro->moneda_id);
    }

    public function test_inscripcion_legacy_sin_moneda_cobra_en_la_principal(): void
    {
        $this->monedas();
        $inscripcion = $this->inscripcion(null, 5000);

        $cobro = app(CobroService::class)->registrar($inscripcion, [
            'monto' => 5000,
            'fecha_pago' => '2026-08-15',
        ]);

        $this->assertSame($this->principal->id, $cobro->moneda_id);
    }

    public function test_moneda_explicita_del_llamador_gana(): void
    {
        $this->monedas();
        $inscripcion = $this->inscripcion(null, 80);

        $cobro = app(CobroService::class)->registrar($inscripcion, [
            'monto' => 80,
            'moneda_id' => $this->secundaria->id,
        ]);

        $this->assertSame($this->secundaria->id, $cobro->moneda_id);
    }

    public function test_cuota_de_membresia_cobra_en_la_principal(): void
    {
        $this->monedas();

        $user = User::create([
            'name' => 'Cuota Moneda',
            'email' => 'cuota.moneda' . uniqid() . '@example.com',
            'password' => Hash::make('x'),
        ]);
        $membresia = Membresia::create(['nombre' => 'MCM' . substr(uniqid(), -6), 'valor' => 9000]);

        $cuota = EstadoCuentaMembresia::create([
            'user_id' => $user->id,
            'membresia_id' => $membresia->id,
            'mes_pagado' => '2026-08',
            'importe' => 9000,
            'observaciones' => '',
            'info_pago' => '',
            'pagado' => true,
            'fecha_pago' => '2026-08-15',
            'estado' => EstadoCuentaMembresia::ESTADO_ACTIVA,
        ]);

        app(CobroService::class)->sincronizarMembresia($cuota);

        $cobro = $cuota->cobros()->first();
        $this->assertNotNull($cobro);
        $this->assertSame($this->principal->id, $cobro->moneda_id);
    }

    public function test_comprobante_a_revisar_tambien_lleva_la_moneda(): void
    {
        $this->monedas();
        $inscripcion = $this->inscripcion($this->secundaria->id, 200);

        $imagenId = DB::table('imagenes')->insertGetId([
            'nombre' => 'comprobante.jpg',
            'ruta' => 'comprobantes/test-' . uniqid() . '.jpg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $cobro = app(CobroService::class)->registrarComprobanteARevisar($inscripcion, $imagenId);

        $this->assertSame(Cobro::ESTADO_A_REVISAR, $cobro->estado);
        $this->assertSame($this->secundaria->id, $cobro->moneda_id);
    }
}

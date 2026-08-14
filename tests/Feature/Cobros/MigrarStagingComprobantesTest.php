<?php

namespace Tests\Feature\Cobros;

use App\Models\Actividad;
use App\Models\Cobro;
use App\Models\EsquemaPrecio;
use App\Models\EsquemaPrecioMembresia;
use App\Models\Imagen;
use App\Models\Inscripcion;
use App\Models\InscripcionComprobante;
use App\Models\Membresia;
use App\Models\Modalidad;
use App\Models\TipoActividad;
use App\Models\User;
use App\Services\CobroService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * `cobros:migrar-staging`: migra el staging `inscripcion_comprobantes` al ledger
 * (cobro `a_revisar` origen checkout), salteando imágenes ya enlazadas a un cobro
 * de la inscripción. Idempotente: re-correrlo no duplica.
 */
class MigrarStagingComprobantesTest extends TestCase
{
    use DatabaseTransactions;

    private function inscripcion(float $monto = 10000): Inscripcion
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);

        $general = Membresia::create(['nombre' => 'Sin membresía', 'valor' => 0]);
        $esquema = EsquemaPrecio::create(['nombre' => 'Esquema Migrar Staging']);
        EsquemaPrecioMembresia::create([
            'esquema_precio_id' => $esquema->id,
            'membresia_id' => $general->id,
            'moneda_id' => DB::table('monedas')->value('id'),
            'precio' => $monto,
        ]);

        $actividad = Actividad::create([
            'nombre' => 'Evento Migrar Staging',
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo MS'])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Presencial MS'])->id,
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

    private function imagen(string $nombre): Imagen
    {
        return Imagen::create(['nombre' => $nombre, 'ruta' => "comprobantes/{$nombre}"]);
    }

    public function test_staging_sin_cobro_crea_cobro_a_revisar_y_es_idempotente(): void
    {
        $inscripcion = $this->inscripcion(10000);
        $imagen = $this->imagen('staging-solo.jpg');
        InscripcionComprobante::create([
            'inscripcion_id' => $inscripcion->id,
            'imagen_id' => $imagen->id,
            'descripcion' => 'Del checkout viejo',
        ]);

        $this->artisan('cobros:migrar-staging')->assertExitCode(0);

        $cobro = $inscripcion->cobros()->sole();
        $this->assertSame(Cobro::ESTADO_A_REVISAR, $cobro->estado);
        $this->assertSame('checkout', $cobro->origen);
        $this->assertEquals(10000, (float) $cobro->monto);
        $this->assertNull($cobro->fecha_pago);
        $this->assertSame(1, $cobro->comprobantes()->count());
        $this->assertSame('Del checkout viejo', $cobro->comprobantes()->first()->descripcion);
        $this->assertEquals($imagen->id, $cobro->comprobantes()->first()->imagen_id);

        // Re-corrida: no duplica ni cobros ni comprobantes.
        $this->artisan('cobros:migrar-staging')->assertExitCode(0);
        $this->assertSame(1, $inscripcion->cobros()->count());
        $this->assertSame(1, $cobro->comprobantes()->count());
    }

    public function test_imagen_ya_enlazada_a_un_cobro_se_saltea(): void
    {
        $inscripcion = $this->inscripcion(10000);
        $imagen = $this->imagen('ya-enlazada.jpg');

        // El cobro confirmado ya tiene la imagen (caso backfill histórico).
        app(CobroService::class)->registrar($inscripcion, [
            'monto' => 10000,
            'origen' => 'backfill',
            'comprobante_ids' => [$imagen->id],
        ], recalcular: false);
        InscripcionComprobante::create([
            'inscripcion_id' => $inscripcion->id,
            'imagen_id' => $imagen->id,
        ]);

        $this->artisan('cobros:migrar-staging')->assertExitCode(0);

        // No se crea cobro a revisar ni se duplica el enlace.
        $this->assertSame(1, $inscripcion->cobros()->count());
        $this->assertSame(0, $inscripcion->cobros()->aRevisar()->count());
        $this->assertSame(1, $inscripcion->cobros()->first()->comprobantes()->count());
    }
}

<?php

namespace Tests\Feature\Pos;

use App\Models\Actividad;
use App\Models\Comida;
use App\Models\EsquemaPrecio;
use App\Models\EsquemaPrecioMembresia;
use App\Models\Membresia;
use App\Models\Modalidad;
use App\Models\Moneda;
use App\Models\TipoActividad;
use App\Models\Transporte;
use App\Models\User;
use App\Services\InscripcionActividadService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * POS multi-moneda: cotizar() separa la porción en la moneda pedida de la
 * porción en la principal (montoMonedaPrincipal), y datosActividad ya no
 * revienta por pedir transportes.nombre (columna inexistente).
 */
class PosMonedaTest extends TestCase
{
    use DatabaseTransactions;

    private Moneda $principal;
    private Moneda $secundaria;
    private Comida $comidaConSecundaria;
    private Comida $comidaSoloPrincipal;

    private function actividadMultiMoneda(): Actividad
    {
        $this->principal = Moneda::principal()
            ?? Moneda::create(['nombre' => 'Pesos Test', 'simbolo' => '$', 'es_principal' => true]);
        $this->secundaria = Moneda::create(['nombre' => 'Dolar Test ' . uniqid(), 'simbolo' => 'U$T']);

        $general = Membresia::create(['nombre' => 'Sin membresía', 'valor' => 0]);
        $esquema = EsquemaPrecio::create(['nombre' => 'Esquema POS MM']);
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

        $actividad = Actividad::create([
            'nombre' => 'Evento POS MM',
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo PosMM'])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Presencial PosMM'])->id,
            'esquema_precio_id' => $esquema->id,
            'entidad_id' => DB::table('entidades')->value('id'),
            'fecha_inicio' => '2026-10-05 10:00:00',
            'fecha_fin' => '2026-10-06 20:00:00',
            'estado' => true,
        ]);

        $this->comidaConSecundaria = Comida::create(['nombre' => 'Comida POS USD', 'descripcion' => 'x', 'precio' => 1000, 'vegano' => false, 'celiaco' => false]);
        $this->comidaConSecundaria->precios()->create(['moneda_id' => $this->secundaria->id, 'precio' => 10]);
        $this->comidaSoloPrincipal = Comida::create(['nombre' => 'Comida POS Pesos', 'descripcion' => 'x', 'precio' => 2000, 'vegano' => false, 'celiaco' => false]);
        $actividad->comidas()->attach([$this->comidaConSecundaria->id, $this->comidaSoloPrincipal->id]);

        return $actividad;
    }

    public function test_cotizar_en_moneda_secundaria_divide_el_total(): void
    {
        $actividad = $this->actividadMultiMoneda();

        $cotizacion = app(InscripcionActividadService::class)->cotizar([
            'actividad_id' => $actividad->id,
            'moneda_id' => $this->secundaria->id,
            'comidas_ids' => [$this->comidaConSecundaria->id, $this->comidaSoloPrincipal->id],
        ]);

        $this->assertSame(110.0, $cotizacion['montoApagar']);
        $this->assertSame(10.0, $cotizacion['montoComidas']);
        $this->assertSame(2000.0, $cotizacion['montoMonedaPrincipal']);
    }

    public function test_cotizar_sin_moneda_usa_planos(): void
    {
        $actividad = $this->actividadMultiMoneda();

        $cotizacion = app(InscripcionActividadService::class)->cotizar([
            'actividad_id' => $actividad->id,
            'comidas_ids' => [$this->comidaConSecundaria->id, $this->comidaSoloPrincipal->id],
        ]);

        $this->assertSame(13000.0, $cotizacion['montoApagar']);
        $this->assertSame(0.0, $cotizacion['montoMonedaPrincipal']);
    }

    public function test_datos_actividad_incluye_transportes_sin_sql_error(): void
    {
        $actividad = $this->actividadMultiMoneda();
        $transporte = Transporte::create(['descripcion' => 'Bus POS MM', 'precio' => 800]);
        $actividad->transportes()->attach($transporte->id);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->getJson(route('pos.actividad-datos', $actividad->id))
            ->assertOk()
            ->assertJsonPath('actividad.transportes.0.nombre', 'Bus POS MM');
    }
}

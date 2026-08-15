<?php

namespace Tests\Feature\GridActividades;

use App\Models\Actividad;
use App\Models\Comida;
use App\Models\EsquemaPrecio;
use App\Models\EsquemaPrecioMembresia;
use App\Models\Membresia;
use App\Models\Modalidad;
use App\Models\Moneda;
use App\Models\TipoActividad;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Serialización de precios por moneda: la pantalla de pago emite
 * precios_por_moneda en los servicios (lo que consume resolverPrecioItemEnMoneda)
 * y la grilla pública NO lo emite (protege contra un append global accidental,
 * que engordaría el payload de todas las actividades).
 */
class PagoSerializaPreciosTest extends TestCase
{
    use DatabaseTransactions;

    private function actividadConComida(): array
    {
        $principal = Moneda::principal()
            ?? Moneda::create(['nombre' => 'Pesos Test', 'simbolo' => '$', 'es_principal' => true]);
        $secundaria = Moneda::create(['nombre' => 'Dolar Test ' . uniqid(), 'simbolo' => 'U$T']);

        $general = Membresia::create(['nombre' => 'Sin membresía', 'valor' => 0]);
        $esquema = EsquemaPrecio::create(['nombre' => 'Esquema Serializa']);
        EsquemaPrecioMembresia::create([
            'esquema_precio_id' => $esquema->id,
            'membresia_id' => $general->id,
            'moneda_id' => $principal->id,
            'precio' => 5000,
        ]);

        $actividad = Actividad::create([
            'nombre' => 'Evento Serializa',
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo Ser'])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Presencial Ser'])->id,
            'esquema_precio_id' => $esquema->id,
            'entidad_id' => DB::table('entidades')->value('id'),
            'fecha_inicio' => '2026-12-01 10:00:00',
            'fecha_fin' => '2026-12-02 20:00:00',
            'estado' => true,
        ]);

        $comida = Comida::create(['nombre' => 'Comida Serializa', 'descripcion' => 'x', 'precio' => 700, 'vegano' => false, 'celiaco' => false]);
        $comida->precios()->create(['moneda_id' => $secundaria->id, 'precio' => 7]);
        $actividad->comidas()->attach($comida->id);

        return [$actividad, $principal, $secundaria];
    }

    public function test_pago_emite_precios_por_moneda_y_moneda_principal(): void
    {
        [$actividad, $principal, $secundaria] = $this->actividadConComida();

        $this->withSession(['grid_pago' => [
            'actividad_id' => $actividad->id,
            'user_id' => null,
            'guest' => ['name' => 'G', 'email' => 'g' . uniqid() . '@example.com', 'pais_id' => 1, 'provincia_id' => 1, 'registrar_datos' => false],
            'comprobante_path' => null,
            'pago_metodo' => null,
        ]])->get("/grid-actividades/pago/{$actividad->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('GridActividades/Pago')
                ->where('monedaPrincipal.id', (int) $principal->id)
                ->has('actividad.comidas.0.precios_por_moneda', 2)
                ->where('actividad.comidas.0.precios_por_moneda.0.es_principal', true)
                ->where('actividad.comidas.0.precios_por_moneda.1.moneda_id', (int) $secundaria->id)
            );
    }

    public function test_grilla_publica_no_emite_precios_por_moneda(): void
    {
        $this->actividadConComida();

        $resp = $this->get('/grid-actividades')->assertOk();
        $this->assertStringNotContainsString('precios_por_moneda', $resp->getContent());
    }
}

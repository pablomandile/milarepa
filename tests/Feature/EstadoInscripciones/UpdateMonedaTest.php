<?php

namespace Tests\Feature\EstadoInscripciones;

use App\Models\Actividad;
use App\Models\Comida;
use App\Models\EsquemaPrecio;
use App\Models\Inscripcion;
use App\Models\Modalidad;
use App\Models\Moneda;
use App\Models\TipoActividad;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Edición admin multi-moneda: el recálculo usa la moneda de la inscripción
 * (null legacy = principal, precios planos). Sin esto, editar una inscripción
 * cotizada en otra moneda la re-cotizaba en pesos.
 */
class UpdateMonedaTest extends TestCase
{
    use DatabaseTransactions;

    private Moneda $principal;
    private Moneda $secundaria;
    private Comida $comidaConSecundaria;   // plana 1000, secundaria 10
    private Comida $comidaSoloPrincipal;   // plana 2000

    private function actividadConComidas(): Actividad
    {
        $this->principal = Moneda::principal()
            ?? Moneda::create(['nombre' => 'Pesos Test', 'simbolo' => '$', 'es_principal' => true]);
        $this->secundaria = Moneda::create(['nombre' => 'Dolar Test ' . uniqid(), 'simbolo' => 'U$T']);

        $actividad = Actividad::create([
            'nombre' => 'Evento Edit Moneda',
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo EditM'])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Presencial EditM'])->id,
            'esquema_precio_id' => EsquemaPrecio::create(['nombre' => 'Esquema EditM'])->id,
            'entidad_id' => DB::table('entidades')->value('id'),
            'fecha_inicio' => '2026-11-01 10:00:00',
            'fecha_fin' => '2026-11-02 20:00:00',
            'estado' => true,
        ]);

        $this->comidaConSecundaria = Comida::create(['nombre' => 'Comida EditM USD', 'descripcion' => 'x', 'precio' => 1000, 'vegano' => false, 'celiaco' => false]);
        $this->comidaConSecundaria->precios()->create(['moneda_id' => $this->secundaria->id, 'precio' => 10]);
        $this->comidaSoloPrincipal = Comida::create(['nombre' => 'Comida EditM Pesos', 'descripcion' => 'x', 'precio' => 2000, 'vegano' => false, 'celiaco' => false]);
        $actividad->comidas()->attach([$this->comidaConSecundaria->id, $this->comidaSoloPrincipal->id]);

        return $actividad;
    }

    private function crearInscripcion(Actividad $actividad, ?int $monedaId, float $monto): Inscripcion
    {
        $user = User::create(['name' => 'Edit Moneda', 'email' => 'edit.moneda' . uniqid() . '@example.com', 'password' => Hash::make('x')]);

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

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_update_recalcula_en_la_moneda_de_la_inscripcion(): void
    {
        $actividad = $this->actividadConComidas();
        $inscripcion = $this->crearInscripcion($actividad, $this->secundaria->id, 100);

        $this->actingAs($this->admin())->putJson(route('estadoinscripciones.update', $inscripcion->id), [
            'pago' => 'Pendiente',
            'comidas_ids' => [$this->comidaConSecundaria->id, $this->comidaSoloPrincipal->id],
        ])->assertOk();

        $inscripcion->refresh();
        // 100 actividad + 10 comida en la moneda de la inscripción.
        $this->assertSame(110.0, (float) $inscripcion->montoapagar);
        $this->assertSame(10.0, (float) $inscripcion->montoComidas);
        // La comida sin precio en esa moneda va a la porción principal.
        $this->assertSame(2000.0, (float) $inscripcion->monto_moneda_principal);
        // La moneda de la inscripción NO se toca.
        $this->assertSame((int) $this->secundaria->id, (int) $inscripcion->moneda_id);
    }

    public function test_update_legacy_sin_moneda_usa_precios_planos(): void
    {
        $actividad = $this->actividadConComidas();
        $inscripcion = $this->crearInscripcion($actividad, null, 10000);

        $this->actingAs($this->admin())->putJson(route('estadoinscripciones.update', $inscripcion->id), [
            'pago' => 'Pendiente',
            'comidas_ids' => [$this->comidaConSecundaria->id, $this->comidaSoloPrincipal->id],
        ])->assertOk();

        $inscripcion->refresh();
        // Comportamiento histórico intacto: 10000 + 1000 + 2000.
        $this->assertSame(13000.0, (float) $inscripcion->montoapagar);
        $this->assertSame(3000.0, (float) $inscripcion->montoComidas);
        $this->assertNull($inscripcion->monto_moneda_principal);
        $this->assertNull($inscripcion->moneda_id);
    }

    public function test_editar_data_incluye_precios_por_moneda_y_moneda(): void
    {
        $actividad = $this->actividadConComidas();
        $inscripcion = $this->crearInscripcion($actividad, $this->secundaria->id, 100);

        $resp = $this->actingAs($this->admin())
            ->getJson(route('estadoinscripciones.editar-data', ['estadoinscripcion' => $inscripcion->id]))
            ->assertOk();

        $resp->assertJsonPath('moneda.id', (int) $this->secundaria->id);
        $resp->assertJsonPath('moneda_principal.id', (int) $this->principal->id);

        $comidas = collect($resp->json('actividad.comidas'));
        $conSecundaria = $comidas->firstWhere('id', $this->comidaConSecundaria->id);
        $this->assertNotNull($conSecundaria);
        // Fila principal sintética + fila de la secundaria.
        $this->assertCount(2, $conSecundaria['precios_por_moneda']);
        $this->assertTrue($conSecundaria['precios_por_moneda'][0]['es_principal']);
    }
}

<?php

namespace Tests\Feature\OracionesCantadas;

use App\Models\OracionCantada;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Excepción por fecha "Sin actividad": ese día no hay oración, así que la fecha no
 * se genera (ni en el calendario, ni en la página pública, ni en actividades online).
 */
class ExcepcionSinActividadTest extends TestCase
{
    use DatabaseTransactions;

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    /** Oración diaria los martes de diciembre 2030 (3, 10, 17, 24, 31) a las 19:00. */
    private function oracionMartes(array $excepciones = []): OracionCantada
    {
        return OracionCantada::create([
            'nombre' => 'Oración de prueba',
            'descripcion' => 'Descripción de prueba',
            'periodicidad' => 'Diaria',
            'dias_semana' => ['martes'],
            'hora' => '19:00',
            'mostrar_en_calendario' => true,
            'excepciones_por_fecha' => $excepciones ?: null,
        ]);
    }

    private function fechasDeDiciembre(OracionCantada $oracion): array
    {
        $inicio = Carbon::create(2030, 12, 1)->startOfMonth();

        return collect($oracion->sesionesDelMes($inicio, $inicio->copy()->endOfMonth()))
            ->pluck('fecha')->all();
    }

    public function test_la_fecha_sin_actividad_no_se_genera(): void
    {
        $oracion = $this->oracionMartes([
            ['fecha' => '2030-12-10', 'hora' => null, 'mensaje' => null, 'sin_actividad' => true],
            ['fecha' => '2030-12-17', 'hora' => '20:00', 'mensaje' => null, 'sin_actividad' => false],
        ]);

        $sesiones = collect($oracion->sesionesDelMes(
            Carbon::create(2030, 12, 1), Carbon::create(2030, 12, 31)
        ))->keyBy('fecha');

        $this->assertSame(['2030-12-03', '2030-12-17', '2030-12-24', '2030-12-31'], $sesiones->keys()->all());
        // Las demás excepciones siguen funcionando igual.
        $this->assertSame('20:00', $sesiones['2030-12-17']['hora']);
    }

    public function test_mensual_sin_actividad_no_genera_la_unica_fecha(): void
    {
        $oracion = OracionCantada::create([
            'nombre' => 'Oración mensual',
            'descripcion' => 'Descripción',
            'periodicidad' => 'Mensual',
            'dia' => 15,
            'hora' => '10:00',
            'mostrar_en_calendario' => true,
            'excepciones_por_fecha' => [['fecha' => '2030-12-15', 'sin_actividad' => true]],
        ]);

        $this->assertSame([], $this->fechasDeDiciembre($oracion));
    }

    public function test_se_guarda_desde_el_formulario_sin_hora_ni_mensaje(): void
    {
        $oracion = $this->oracionMartes();

        $this->actingAs($this->admin())
            ->put(route('oracionescantadas.update', $oracion->id), [
                'nombre' => $oracion->nombre,
                'descripcion' => $oracion->descripcion,
                'periodicidad' => 'Diaria',
                'dias_semana' => ['martes'],
                'hora' => '19:00',
                'mostrar_en_calendario' => true,
                'excepciones_por_fecha' => [
                    ['fecha' => '2030-12-10', 'hora' => '', 'mensaje' => '', 'sin_actividad' => true],
                ],
            ])
            ->assertSessionHasNoErrors();

        $excepcion = $oracion->fresh()->excepcionParaFecha('2030-12-10');
        $this->assertTrue($excepcion['sin_actividad']);
        $this->assertNull($excepcion['hora']);
        $this->assertNull($excepcion['mensaje']);
        $this->assertNotContains('2030-12-10', $this->fechasDeDiciembre($oracion->fresh()));
    }

    public function test_sin_hora_mensaje_ni_sin_actividad_sigue_siendo_error(): void
    {
        $oracion = $this->oracionMartes();

        $this->actingAs($this->admin())
            ->put(route('oracionescantadas.update', $oracion->id), [
                'nombre' => $oracion->nombre,
                'descripcion' => $oracion->descripcion,
                'periodicidad' => 'Diaria',
                'dias_semana' => ['martes'],
                'hora' => '19:00',
                'mostrar_en_calendario' => true,
                'excepciones_por_fecha' => [
                    ['fecha' => '2030-12-10', 'hora' => '', 'mensaje' => '', 'sin_actividad' => false],
                ],
            ])
            ->assertSessionHasErrors('excepciones_por_fecha.0.hora');
    }

    public function test_el_calendario_no_muestra_la_fecha_sin_actividad(): void
    {
        $oracion = $this->oracionMartes([
            ['fecha' => '2030-12-10', 'hora' => null, 'mensaje' => null, 'sin_actividad' => true],
        ]);

        $this->actingAs($this->admin())->get(route('calendario.index', ['month' => '2030-12']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Calendario/Index')
                ->where('oracionesCantadas', function ($items) use ($oracion) {
                    $fechas = collect($items)->where('id', $oracion->id)->pluck('fecha')->all();

                    return $fechas === ['2030-12-03', '2030-12-17', '2030-12-24', '2030-12-31'];
                })
            );
    }
}

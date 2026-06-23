<?php

namespace Tests\Feature\Inscripciones;

use App\Models\Actividad;
use App\Models\Entidad;
use App\Models\EsquemaPrecio;
use App\Models\Grabacion;
use App\Models\Modalidad;
use App\Models\TipoActividad;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Issue 2: al crear una inscripción, envioGrabacion solo queda 'Pendiente' si la
 * actividad ofrece grabación (grabacion_id no nulo); de lo contrario debe ser
 * 'No aplica'. (Antes se ponía 'Pendiente' siempre.)
 */
class InscripcionGrabacionDefaultTest extends TestCase
{
    use DatabaseTransactions;

    private function crearActividad(?int $grabacionId): Actividad
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);

        $abrev = strtoupper(substr(md5(uniqid('', true)), 0, 5));

        return Actividad::create([
            'nombre' => 'Evento Grab ' . ($grabacionId ? 'con' : 'sin'),
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo Grab ' . $abrev])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Modalidad Grab ' . $abrev])->id,
            'esquema_precio_id' => EsquemaPrecio::create(['nombre' => 'Esquema Grab ' . $abrev])->id,
            'entidad_id' => Entidad::create(['nombre' => 'Entidad Grab ' . $abrev, 'abreviacion' => $abrev, 'entidad_principal' => false])->id,
            'grabacion_id' => $grabacionId,
            'fecha_inicio' => '2026-06-13 10:00:00',
            'fecha_fin' => '2026-06-14 20:00:00',
            'estado' => true,
        ]);
    }

    private function payload(int $actividadId): array
    {
        return [
            'actividad_id' => $actividadId,
            'membresia' => 'Sin membresía',
            'precioGeneral' => 0,
            'montoapagar' => 0,
            'envioLinkStream' => 'pendiente',
            'asistencia' => 'presente',
            'online' => 0,
        ];
    }

    public function test_actividad_sin_grabacion_marca_envio_grabacion_no_aplica(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $actividad = $this->crearActividad(null);

        $this->actingAs($user)->post(route('inscripciones.store'), $this->payload($actividad->id));

        $this->assertDatabaseHas('inscripciones', [
            'actividad_id' => $actividad->id,
            'user_id' => $user->id,
            'envioGrabacion' => 'No aplica',
        ]);
    }

    public function test_actividad_con_grabacion_marca_envio_grabacion_pendiente(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $grabacion = Grabacion::create(['nombre' => 'Grab ' . uniqid(), 'valor' => 0]);
        $actividad = $this->crearActividad($grabacion->id);

        $this->actingAs($user)->post(route('inscripciones.store'), $this->payload($actividad->id));

        $this->assertDatabaseHas('inscripciones', [
            'actividad_id' => $actividad->id,
            'user_id' => $user->id,
            'envioGrabacion' => 'Pendiente',
        ]);
    }
}

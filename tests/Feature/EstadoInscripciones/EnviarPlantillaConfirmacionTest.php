<?php

namespace Tests\Feature\EstadoInscripciones;

use App\Models\Actividad;
use App\Models\Entidad;
use App\Models\EsquemaPrecio;
use App\Models\Inscripcion;
use App\Models\Modalidad;
use App\Models\TipoActividad;
use App\Models\User;
use App\Services\EmailInscripcionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Issue 1: al enviar la plantilla de confirmación (mail "confirmada"), la inscripción
 * debe quedar en estado 'Confirmada'. Caso típico: inscripciones de costo 0 incluidas
 * en la membresía, que estaban saldadas pero seguían en 'Registrada'.
 */
class EnviarPlantillaConfirmacionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_enviar_plantilla_confirmacion_confirma_la_inscripcion(): void
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);
        Mail::fake();

        $entidad = Entidad::create(['nombre' => 'Entidad Conf Test', 'abreviacion' => 'ZCT', 'entidad_principal' => false]);
        $actividad = Actividad::create([
            'nombre' => 'Evento Conf Test',
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo Conf'])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Modalidad Conf'])->id,
            'esquema_precio_id' => EsquemaPrecio::create(['nombre' => 'Esquema Conf'])->id,
            'entidad_id' => $entidad->id,
            'fecha_inicio' => '2026-06-13 10:00:00',
            'fecha_fin' => '2026-06-14 20:00:00',
            'estado' => true,
        ]);
        $user = User::create(['name' => 'Conf User', 'email' => 'conf.user@example.com', 'password' => Hash::make('x')]);

        // Inscripción de costo 0 (incluida en membresía): saldada pero aún "Registrada".
        $inscripcion = Inscripcion::create([
            'actividad_id' => $actividad->id,
            'user_id' => $user->id,
            'membresia' => 'Sin membresía',
            'precioGeneral' => 0,
            'montoapagar' => 0,
            'pago' => 'Saldado',
            'estado' => 'Registrada',
            'envioLinkStream' => 'No aplica',
            'envioConfirmacion' => 'Pendiente',
            'envioGrabacion' => 'No aplica',
            'asistencia' => 'Pendiente',
            'online' => false,
        ]);

        $ok = EmailInscripcionService::enviarPlantillaConfirmacion($inscripcion);

        $this->assertTrue($ok);
        $inscripcion->refresh();
        $this->assertSame('Confirmada', $inscripcion->estado);
        $this->assertSame('Enviada', $inscripcion->envioConfirmacion);
        $this->assertSame('Enviada', $inscripcion->envioRegistro);
    }
}

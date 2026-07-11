<?php

namespace Tests\Feature\EstadoInscripciones;

use App\Models\Actividad;
use App\Models\Entidad;
use App\Models\EsquemaPrecio;
use App\Models\Inscripcion;
use App\Models\Imagen;
use App\Models\InscripcionComprobante;
use App\Models\Modalidad;
use App\Models\TipoActividad;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Borrado de inscripciones (EstadoInscripcionesController@destroy).
 * Cubre: autorización (admin/editor sí, asistant 403, sin auth redirige),
 * cascada de comprobantes (cascadeOnDelete) y borrado del archivo en disco.
 */
class EstadoInscripcionesDestroyTest extends TestCase
{
    use DatabaseTransactions;

    /** users.pais_id es NOT NULL DEFAULT 1 con FK a paises. */
    private function asegurarPaisDefault(): void
    {
        DB::statement('INSERT IGNORE INTO paises (id, nombre, created_at, updated_at) VALUES (1, ?, NOW(), NOW())', ['Argentina']);
    }

    /** Crea una inscripción con su actividad/usuario y la devuelve. */
    private function crearInscripcion(): Inscripcion
    {
        $this->asegurarPaisDefault();

        $entidad = Entidad::create(['nombre' => 'Entidad Destroy Test', 'abreviacion' => 'EDT', 'entidad_principal' => false]);

        $actividad = Actividad::create([
            'nombre' => 'Evento Destroy Test',
            'tipo_actividad_id' => TipoActividad::create(['nombre' => 'Tipo Destroy'])->id,
            'modalidad_id' => Modalidad::create(['nombre' => 'Modalidad Destroy'])->id,
            'esquema_precio_id' => EsquemaPrecio::create(['nombre' => 'Esquema Destroy'])->id,
            'entidad_id' => $entidad->id,
            'fecha_inicio' => '2026-06-13 10:00:00',
            'fecha_fin' => '2026-06-14 20:00:00',
            'estado' => true,
        ]);

        $user = User::create([
            'name' => 'Inscripto Destroy',
            'email' => 'insc.destroy@example.com',
            'password' => Hash::make('x'),
        ]);

        return Inscripcion::create([
            'actividad_id' => $actividad->id,
            'user_id' => $user->id,
            'membresia' => 'Sin membresía',
            'precioGeneral' => 0,
            'montoapagar' => 0,
            'pago' => 'Pendiente',
            'estado' => 'Registrada',
            'envioLinkStream' => 'No aplica',
            'asistencia' => 'Pendiente',
            'online' => false,
        ]);
    }

    public function test_admin_borra_inscripcion_con_cascada_de_comprobantes_y_archivo(): void
    {
        Storage::fake('public');
        $inscripcion = $this->crearInscripcion();

        // Comprobante con archivo real en el disco fake.
        Storage::disk('public')->put('comprobantes/destroy-test.jpg', 'contenido');
        $imagen = Imagen::create(['nombre' => 'destroy-test.jpg', 'ruta' => 'comprobantes/destroy-test.jpg']);
        InscripcionComprobante::create([
            'inscripcion_id' => $inscripcion->id,
            'imagen_id' => $imagen->id,
            'descripcion' => 'Test',
        ]);
        Storage::disk('public')->assertExists('comprobantes/destroy-test.jpg');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->delete(route('estadoinscripciones.destroy', $inscripcion->id));

        $response->assertStatus(302);
        $this->assertDatabaseMissing('inscripciones', ['id' => $inscripcion->id]);
        // cascadeOnDelete elimina la fila hija.
        $this->assertDatabaseMissing('inscripcion_comprobantes', ['inscripcion_id' => $inscripcion->id]);
        // El archivo del comprobante se borra del disco.
        Storage::disk('public')->assertMissing('comprobantes/destroy-test.jpg');
    }

    public function test_editor_puede_borrar_inscripcion(): void
    {
        $inscripcion = $this->crearInscripcion();

        $editor = User::factory()->create();
        $editor->assignRole('editor');

        $response = $this->actingAs($editor)
            ->delete(route('estadoinscripciones.destroy', $inscripcion->id));

        $response->assertStatus(302);
        $this->assertDatabaseMissing('inscripciones', ['id' => $inscripcion->id]);
    }

    public function test_asistant_no_puede_borrar_inscripcion(): void
    {
        $inscripcion = $this->crearInscripcion();

        $asistant = User::factory()->create();
        $asistant->assignRole('asistant');

        $response = $this->actingAs($asistant)
            ->delete(route('estadoinscripciones.destroy', $inscripcion->id));

        $response->assertStatus(403);
        $this->assertDatabaseHas('inscripciones', ['id' => $inscripcion->id]);
    }

    public function test_usuario_no_autenticado_no_puede_borrar(): void
    {
        $inscripcion = $this->crearInscripcion();

        $response = $this->delete(route('estadoinscripciones.destroy', $inscripcion->id));

        $response->assertStatus(302); // redirige a login (auth:sanctum)
        $this->assertDatabaseHas('inscripciones', ['id' => $inscripcion->id]);
    }
}

<?php

namespace Tests\Feature\ImageUpload;

use App\Models\Imagen;
use App\Models\Maestro;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Subida de imagen diferida en Maestros (flujo completo HTTP).
 * Las rutas de maestros solo requieren autenticación (sin permiso específico).
 */
class MaestroImageUploadTest extends ImageUploadTestCase
{
    public function test_crear_con_imagen_sube_y_asocia_la_imagen(): void
    {
        $user = $this->usuarioAutenticado();
        $imagenesAntes = Imagen::count();

        $response = $this->actingAs($user)->post(route('maestros.store'), [
            'nombre' => 'Gen Test',
            'email'  => 'gen.test@example.com',
            'imagen' => UploadedFile::fake()->image('maestro.jpg'),
        ]);

        $response->assertRedirect(route('maestros.index'));

        $maestro = Maestro::where('email', 'gen.test@example.com')->first();
        $this->assertNotNull($maestro);
        $this->assertNotNull($maestro->imagen_id);
        $this->assertSame($imagenesAntes + 1, Imagen::count());
        Storage::disk('public')->assertExists($maestro->imagen->ruta);
    }

    public function test_validacion_fallida_no_crea_imagen_huerfana(): void
    {
        $user = $this->usuarioAutenticado();
        $imagenesAntes = Imagen::count();
        $maestrosAntes = Maestro::count();

        $response = $this->actingAs($user)->post(route('maestros.store'), [
            // faltan 'nombre' y 'email' (required)
            'imagen' => UploadedFile::fake()->image('maestro.jpg'),
        ]);

        $response->assertSessionHasErrors(['nombre', 'email']);
        $this->assertSame($maestrosAntes, Maestro::count());
        $this->assertSame($imagenesAntes, Imagen::count(), 'No debe quedar Imagen huérfana al fallar la validación.');
        $this->assertEmpty(Storage::disk('public')->allFiles('img/maestros'));
    }

    public function test_editar_sin_nueva_imagen_conserva_la_actual(): void
    {
        $user = $this->usuarioAutenticado();

        $imagen = Imagen::create(['nombre' => 'orig.jpg', 'ruta' => 'img/maestros/orig.jpg']);
        $maestro = Maestro::create([
            'nombre'    => 'Gen Orig',
            'email'     => 'gen.keep@example.com',
            'imagen_id' => $imagen->id,
        ]);
        $imagenesAntes = Imagen::count();

        $response = $this->actingAs($user)->put(route('maestros.update', $maestro->id), [
            'nombre'    => 'Gen Editado',
            'email'     => 'gen.keep@example.com',
            'imagen_id' => $imagen->id,
            // sin 'imagen'
        ]);

        $response->assertRedirect(route('maestros.index'));
        $maestro->refresh();
        $this->assertSame('Gen Editado', $maestro->nombre);
        $this->assertSame($imagen->id, $maestro->imagen_id);
        $this->assertSame($imagenesAntes, Imagen::count(), 'No debe crearse una nueva Imagen al editar sin archivo.');
    }

    public function test_editar_con_nueva_imagen_reemplaza_la_asociacion(): void
    {
        $user = $this->usuarioAutenticado();

        $imagen = Imagen::create(['nombre' => 'orig.jpg', 'ruta' => 'img/maestros/orig.jpg']);
        $maestro = Maestro::create([
            'nombre'    => 'Gen Orig',
            'email'     => 'gen.replace@example.com',
            'imagen_id' => $imagen->id,
        ]);
        $imagenesAntes = Imagen::count();

        $response = $this->actingAs($user)->put(route('maestros.update', $maestro->id), [
            'nombre'    => 'Gen Orig',
            'email'     => 'gen.replace@example.com',
            'imagen_id' => $imagen->id,
            'imagen'    => UploadedFile::fake()->image('nueva.jpg'),
        ]);

        $response->assertRedirect(route('maestros.index'));
        $maestro->refresh();
        $this->assertNotNull($maestro->imagen_id);
        $this->assertNotSame($imagen->id, $maestro->imagen_id, 'Debe asociarse la nueva imagen.');
        $this->assertSame($imagenesAntes + 1, Imagen::count());
        Storage::disk('public')->assertExists($maestro->imagen->ruta);
    }
}

<?php

namespace Tests\Feature\ImageUpload;

use App\Models\Imagen;
use App\Models\Libro;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Subida de imagen diferida en Libros (flujo completo HTTP).
 */
class LibroImageUploadTest extends ImageUploadTestCase
{
    public function test_crear_con_imagen_sube_y_asocia_la_imagen(): void
    {
        $user = $this->usuarioAutenticado();
        $imagenesAntes = Imagen::count();

        $response = $this->actingAs($user)->post(route('libros.store'), [
            'titulo' => 'El camino gozoso de buena fortuna',
            'precio' => 12000,
            'imagen' => UploadedFile::fake()->image('libro.jpg'),
        ]);

        $response->assertRedirect(route('libros.index'));

        $libro = Libro::where('titulo', 'El camino gozoso de buena fortuna')->first();
        $this->assertNotNull($libro);
        $this->assertNotNull($libro->imagen_id);
        $this->assertSame($imagenesAntes + 1, Imagen::count());
        Storage::disk('public')->assertExists($libro->imagen->ruta);
    }

    public function test_validacion_fallida_no_crea_imagen_huerfana(): void
    {
        $user = $this->usuarioAutenticado();
        $imagenesAntes = Imagen::count();
        $librosAntes = Libro::count();

        $response = $this->actingAs($user)->post(route('libros.store'), [
            // falta 'titulo' y 'precio' (required)
            'imagen' => UploadedFile::fake()->image('libro.jpg'),
        ]);

        $response->assertSessionHasErrors(['titulo', 'precio']);
        $this->assertSame($librosAntes, Libro::count());
        $this->assertSame($imagenesAntes, Imagen::count(), 'No debe quedar Imagen huérfana al fallar la validación.');
        $this->assertEmpty(Storage::disk('public')->allFiles('img/libros'));
    }
}

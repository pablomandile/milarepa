<?php

namespace Tests\Feature\ImageUpload;

use App\Models\Imagen;
use App\Models\MetodoPago;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Subida de imagen diferida en Métodos de Pago (flujo completo HTTP).
 */
class MetodoPagoImageUploadTest extends ImageUploadTestCase
{
    public function test_crear_con_imagen_sube_y_asocia_la_imagen(): void
    {
        $user = $this->usuarioAutenticado();
        $imagenesAntes = Imagen::count();

        $response = $this->actingAs($user)->post(route('metodospago.store'), [
            'nombre'       => 'Transferencia Test',
            'descripcion'  => 'Pago por transferencia bancaria',
            'tipo_de_pago' => 'Online',
            'imagen'       => UploadedFile::fake()->image('mpago.jpg'),
        ]);

        $response->assertRedirect(route('metodospago.index'));

        $metodo = MetodoPago::where('nombre', 'Transferencia Test')->first();
        $this->assertNotNull($metodo);
        $this->assertNotNull($metodo->imagen_id);
        $this->assertSame($imagenesAntes + 1, Imagen::count());
        Storage::disk('public')->assertExists($metodo->imagen->ruta);
    }

    public function test_validacion_fallida_no_crea_imagen_huerfana(): void
    {
        $user = $this->usuarioAutenticado();
        $imagenesAntes = Imagen::count();
        $metodosAntes = MetodoPago::count();

        $response = $this->actingAs($user)->post(route('metodospago.store'), [
            // falta 'nombre' y 'descripcion' (required); tipo_de_pago inválido
            'tipo_de_pago' => 'Invalido',
            'imagen'       => UploadedFile::fake()->image('mpago.jpg'),
        ]);

        $response->assertSessionHasErrors(['nombre', 'descripcion', 'tipo_de_pago']);
        $this->assertSame($metodosAntes, MetodoPago::count());
        $this->assertSame($imagenesAntes, Imagen::count(), 'No debe quedar Imagen huérfana al fallar la validación.');
        $this->assertEmpty(Storage::disk('public')->allFiles('img/mpago'));
    }
}

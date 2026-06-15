<?php

namespace Tests\Feature\ImageUpload;

use App\Concerns\ProcesaImagenAlGuardar;
use App\Models\Imagen;
use App\Services\OptimizadorImagenService;
use Closure;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

/**
 * Verifica el comportamiento del trait que centraliza la subida de imagen al
 * guardar: crea el registro Imagen + archivo solo si la persistencia tiene
 * éxito, y limpia todo (registro y archivo físico) si algo falla.
 */
class ProcesaImagenAlGuardarTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Objeto de prueba que usa el trait y expone el método protegido.
     */
    private function harness(): object
    {
        return new class {
            use ProcesaImagenAlGuardar;

            public function ejecutar(?UploadedFile $file, string $carpeta, OptimizadorImagenService $opt, Closure $persist)
            {
                return $this->guardarConImagen($file, $carpeta, $opt, $persist);
            }
        };
    }

    public function test_crea_imagen_y_archivo_cuando_la_persistencia_tiene_exito(): void
    {
        Storage::fake('public');
        $opt = app(OptimizadorImagenService::class);
        $file = UploadedFile::fake()->image('foto.jpg');

        $idRecibido = $this->harness()->ejecutar($file, 'img/test', $opt, fn ($imagenId) => $imagenId);

        $this->assertNotNull($idRecibido);
        $this->assertSame(1, Imagen::where('id', $idRecibido)->count());

        $imagen = Imagen::find($idRecibido);
        Storage::disk('public')->assertExists($imagen->ruta);
    }

    public function test_revierte_imagen_y_borra_archivo_si_la_persistencia_falla(): void
    {
        Storage::fake('public');
        $opt = app(OptimizadorImagenService::class);
        $file = UploadedFile::fake()->image('foto.jpg');
        $imagenesAntes = Imagen::count();

        try {
            $this->harness()->ejecutar($file, 'img/test', $opt, function () {
                throw new RuntimeException('fallo simulado al guardar');
            });
            $this->fail('Se esperaba una RuntimeException.');
        } catch (RuntimeException $e) {
            $this->assertSame('fallo simulado al guardar', $e->getMessage());
        }

        // No quedó registro Imagen huérfano...
        $this->assertSame($imagenesAntes, Imagen::count());
        // ...ni archivo físico huérfano.
        $this->assertEmpty(Storage::disk('public')->allFiles('img/test'));
    }

    public function test_no_crea_imagen_cuando_no_hay_archivo(): void
    {
        Storage::fake('public');
        $opt = app(OptimizadorImagenService::class);
        $imagenesAntes = Imagen::count();

        $idRecibido = $this->harness()->ejecutar(null, 'img/test', $opt, fn ($imagenId) => $imagenId);

        $this->assertNull($idRecibido);
        $this->assertSame($imagenesAntes, Imagen::count());
    }
}

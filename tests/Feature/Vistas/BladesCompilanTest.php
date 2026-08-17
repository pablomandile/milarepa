<?php

namespace Tests\Feature\Vistas;

use Illuminate\Support\Facades\Blade;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Toda vista Blade tiene que compilar a PHP válido.
 *
 * Nació de un mail de confirmación que fallaba en producción con "syntax error,
 * unexpected end of file": la plantilla mezclaba `@php(...)` inline con un bloque
 * `@php ... @endphp` posterior, y el regex de Blade
 * (`/(?<!@)@php(.*?)@endphp/s`) hacía que el inline se tragara todo el medio.
 * La plantilla estaba rota en el repo y en producción por igual; no la cubría
 * ningún test porque los de mails renderizan `inscripcion_registrada`.
 *
 * Un error así no se ve hasta que alguien aprieta "enviar", así que el chequeo va
 * sobre TODAS las vistas y no sobre las que algún test resulta que renderiza.
 */
class BladesCompilanTest extends TestCase
{
    /** @return array<string, string> ruta relativa => contenido */
    private function vistas(): array
    {
        $raiz = resource_path('views');
        $vistas = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($raiz)) as $archivo) {
            if (!$archivo->isFile() || !str_ends_with($archivo->getFilename(), '.blade.php')) {
                continue;
            }

            $relativa = str_replace('\\', '/', substr($archivo->getPathname(), strlen($raiz) + 1));
            $vistas[$relativa] = file_get_contents($archivo->getPathname());
        }

        return $vistas;
    }

    public function test_todas_las_vistas_compilan_a_php_valido(): void
    {
        $vistas = $this->vistas();
        $this->assertNotEmpty($vistas, 'No se encontró ninguna vista Blade.');

        $tmp = tempnam(sys_get_temp_dir(), 'blade_') . '.php';
        $rotas = [];

        foreach ($vistas as $ruta => $contenido) {
            file_put_contents($tmp, Blade::compileString($contenido));
            $salida = (string) shell_exec('php -l ' . escapeshellarg($tmp) . ' 2>&1');

            if (!str_contains($salida, 'No syntax errors')) {
                $rotas[$ruta] = trim(preg_replace('/\s+/', ' ', $salida));
            }
        }

        @unlink($tmp);

        $this->assertSame([], $rotas, "Vistas que no compilan:\n" . json_encode($rotas, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public function test_ninguna_vista_mezcla_php_inline_con_bloques(): void
    {
        // El inline `@php(...)` es válido, pero convivir con un `@endphp` en el
        // mismo archivo lo vuelve una bomba de tiempo: hoy puede andar sólo porque
        // el inline está después del último bloque, y agregar un bloque abajo lo
        // rompe en silencio. Se usa siempre la forma `@php ... @endphp`.
        $mezcladas = [];

        foreach ($this->vistas() as $ruta => $contenido) {
            if (preg_match('/(?<!@)@php\s*\(/', $contenido) && str_contains($contenido, '@endphp')) {
                $mezcladas[] = $ruta;
            }
        }

        $this->assertSame([], $mezcladas, 'Estas vistas mezclan @php(...) inline con bloques @php ... @endphp.');
    }
}

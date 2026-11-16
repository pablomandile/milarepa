<?php

namespace Tests\Feature\Pwa;

use Tests\TestCase;

/**
 * La app es instalable como PWA: manifest con los campos e íconos que exige
 * Chrome, el HTML lo enlaza y captura beforeinstallprompt antes de que monte Vue,
 * y el service worker tiene handler de fetch sin cachear páginas.
 */
class PwaTest extends TestCase
{
    private function manifest(): array
    {
        $json = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);
        $this->assertIsArray($json, 'manifest.webmanifest no es JSON válido');

        return $json;
    }

    public function test_el_manifest_tiene_lo_que_exige_chrome_para_instalar(): void
    {
        $m = $this->manifest();

        foreach (['name', 'short_name', 'start_url', 'scope'] as $campo) {
            $this->assertNotEmpty($m[$campo] ?? null, "Falta '$campo' en el manifest");
        }
        $this->assertContains($m['display'] ?? null, ['standalone', 'fullscreen', 'minimal-ui']);

        $tamanios = collect($m['icons'])->where('purpose', 'any')->pluck('sizes')->all();
        $this->assertContains('192x192', $tamanios);
        $this->assertContains('512x512', $tamanios);
        $this->assertTrue(collect($m['icons'])->contains('purpose', 'maskable'), 'Falta el ícono maskable');
    }

    public function test_los_iconos_del_manifest_existen_con_el_tamanio_declarado(): void
    {
        foreach ($this->manifest()['icons'] as $icono) {
            $ruta = public_path(ltrim(strtok($icono['src'], '?'), '/'));
            $this->assertFileExists($ruta);

            [$ancho, $alto, $tipo] = getimagesize($ruta);
            $this->assertSame($icono['sizes'], "{$ancho}x{$alto}", "Tamaño distinto en {$icono['src']}");
            $this->assertSame(IMAGETYPE_PNG, $tipo, "{$icono['src']} no es PNG");
        }

        $this->assertFileExists(public_path('icons/apple-touch-icon.png'));
    }

    public function test_el_html_enlaza_el_manifest_y_captura_el_evento_de_instalacion(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertStringContainsString('<link rel="manifest" href="/manifest.webmanifest">', $html);
        $this->assertStringContainsString('apple-touch-icon', $html);
        $this->assertStringContainsString('window.__pwaInstall', $html);
        $this->assertStringContainsString("addEventListener('beforeinstallprompt'", $html);
        $this->assertStringContainsString("serviceWorker.register('/sw.js?v=", $html);

        // El hook tiene que estar antes de los bundles de Vite, o el evento se pierde.
        $this->assertLessThan(
            strpos($html, '/build/assets/'),
            strpos($html, 'window.__pwaInstall'),
            'El hook de beforeinstallprompt tiene que ir antes de los scripts de la app'
        );
    }

    public function test_el_service_worker_tiene_fetch_y_no_cachea_paginas(): void
    {
        $sw = file_get_contents(public_path('sw.js'));

        $this->assertStringContainsString("addEventListener('fetch'", $sw);
        // Lo único que se guarda en caché son los assets con hash de /build/.
        $this->assertSame(1, substr_count($sw, 'cache.put('), 'sw.js solo debe cachear /build/');
        $this->assertMatchesRegularExpression("#startsWith\\('/build/'\\)[\\s\\S]*cache\\.put\\(#", $sw);
        // Las navegaciones se detectan por mode (el router de Inertia manda Accept: text/html).
        $this->assertStringContainsString("request.mode === 'navigate'", $sw);
    }

    public function test_htaccess_no_deja_cachear_el_sw_ni_el_manifest(): void
    {
        $htaccess = file_get_contents(public_path('.htaccess'));

        $this->assertStringContainsString('sw\.js|manifest\.webmanifest', $htaccess);
        $this->assertStringContainsString('no-cache, must-revalidate, max-age=0', $htaccess);
        $this->assertStringContainsString('AddType application/manifest+json .webmanifest', $htaccess);
    }
}

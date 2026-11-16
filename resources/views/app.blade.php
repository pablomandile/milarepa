<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- PWA instalable. Al cambiar los íconos: subir ?v= acá y en manifest.webmanifest,
             y el nombre de CACHE en public/sw.js (skill adaptar-a-pwa §6). --}}
        <meta name="theme-color" content="#ffffff">
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="Milarepa">
        <meta name="application-name" content="Milarepa">
        <link rel="manifest" href="/manifest.webmanifest">
        <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png?v=1">
        <link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192.png?v=1">

        {{-- Chrome dispara beforeinstallprompt apenas carga la página, casi siempre ANTES de
             que monte Vue: si se escuchara desde un componente el evento ya habría pasado.
             Se captura acá y el botón "Instalar app" (usePwaInstall) lo lee después. --}}
        <script>
            (function () {
                window.__pwaInstall = { prompt: null, installed: false };
                window.addEventListener('beforeinstallprompt', function (e) {
                    e.preventDefault();
                    window.__pwaInstall.prompt = e;
                    window.dispatchEvent(new CustomEvent('pwa:installable'));
                });
                window.addEventListener('appinstalled', function () {
                    window.__pwaInstall.prompt = null;
                    window.__pwaInstall.installed = true;
                    window.dispatchEvent(new CustomEvent('pwa:installed'));
                });
                // ?v= en la URL del SW: el CDN puede tener cacheada una copia vieja de /sw.js.
                if ('serviceWorker' in navigator) {
                    window.addEventListener('load', function () {
                        navigator.serviceWorker.register('/sw.js?v=1').catch(function () {});
                    });
                }
            })();
        </script>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        {{-- Anti-FOUC: aplicar .dark en <html> antes de cargar Vite. Por defecto tema claro;
             solo se usa oscuro si el usuario lo eligió explícitamente (ignoramos el tema del sistema). --}}
        <script>
            (function () {
                try {
                    var stored = localStorage.getItem('theme');
                    if (stored === 'dark') document.documentElement.classList.add('dark');
                } catch (e) {}
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>

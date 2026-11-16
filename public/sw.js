/*
 * Service worker de Milarepa (PWA instalable).
 *
 * Mínimo a propósito: la app necesita el servidor para todo, así que acá NO se
 * guardan páginas ni datos. Solo:
 *   - /build/  → cache-first (los archivos llevan hash de contenido en el nombre:
 *                un deploy cambia la URL, nunca el contenido de una URL).
 *   - navegaciones → red primero, con la red de seguridad del JSON de Inertia y
 *                una pantalla "Sin conexión" si no hay red. No se cachean.
 *   - todo lo demás (XHR de Inertia, /storage/, íconos, manifest) → directo a la
 *                red, sin tocar: nada con URL fija puede quedar congelado.
 *
 * Si cambia algo de lo que se cachea, subir CACHE: `activate` borra las demás.
 */
const CACHE = 'milarepa-v1';

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim()),
    );
});

const OFFLINE_HTML = `<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sin conexión · Milarepa</title>
<style>
  body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;
       font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#f3f4f6;color:#1f2937}
  .card{max-width:22rem;margin:1rem;padding:2rem;background:#fff;border-radius:1rem;text-align:center;
        box-shadow:0 10px 30px rgba(79,70,229,.12)}
  img{width:96px;height:96px}
  h1{font-size:1.25rem;margin:1rem 0 .5rem}
  p{margin:0 0 1.5rem;color:#4b5563;line-height:1.5}
  button{border:0;border-radius:.5rem;padding:.75rem 1.5rem;background:#4f46e5;color:#fff;font-size:1rem;cursor:pointer}
</style></head>
<body><div class="card">
  <img src="/icons/icon-192.png?v=1" alt="">
  <h1>Sin conexión</h1>
  <p>No hay conexión a internet. Cuando vuelva la señal, tocá Reintentar.</p>
  <button onclick="location.reload()">Reintentar</button>
</div></body></html>`;

const offline = () =>
    new Response(OFFLINE_HTML, {
        status: 503,
        headers: { 'Content-Type': 'text/html; charset=utf-8', 'Cache-Control': 'no-store' },
    });

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;

    // Assets compilados por Vite: hash en el nombre → cache-first.
    if (url.pathname.startsWith('/build/')) {
        event.respondWith(
            caches.open(CACHE).then((cache) =>
                cache.match(request).then((hit) =>
                    hit ||
                    fetch(request).then((res) => {
                        if (res.ok) cache.put(request, res.clone());
                        return res;
                    }),
                ),
            ),
        );
        return;
    }

    // Solo navegaciones de verdad (el router de Inertia manda Accept: text/html
    // en sus XHR: por eso se mira `mode`, no el Accept). Nunca se cachean.
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((respuesta) => {
                    // Red de seguridad (skill inertia-json-crudo §5): si a una
                    // navegación le llegó el JSON de Inertia en vez del HTML, se
                    // vuelve a pedir el HTML salteando la caché HTTP.
                    if (!respuesta.headers.get('x-inertia')) return respuesta;

                    return fetch(request.url, {
                        cache: 'reload',
                        credentials: 'same-origin',
                        headers: { Accept: 'text/html' },
                    }).then((recuperada) =>
                        // Una respuesta ya redirigida (ej. sesión vencida → login)
                        // no se le puede entregar a una navegación: se pasa el
                        // redirect y lo sigue el navegador.
                        recuperada.redirected ? Response.redirect(recuperada.url, 302) : recuperada,
                    );
                })
                .catch(() => offline()),
        );
    }

    // Todo lo demás: sin respondWith → el navegador lo pide a la red como siempre.
});

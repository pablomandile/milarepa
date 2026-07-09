# Grilla de Actividades embebible en meditarenargentina.org (iframe)

> Estado: implementado como vista separada `/grillaembebida`. La página pública `/grid-actividades` quedó intacta.

## Contexto

Se creó una página en el sitio WordPress (meditarenargentina.org) con un iframe apuntando a la grilla de actividades, pero el navegador la bloqueaba: *"Refused to display ... because it set 'X-Frame-Options' to 'deny'"*.

**Causa:** el middleware propio `app/Http/Middleware/SecurityHeaders.php` fija `X-Frame-Options: DENY` en todas las respuestas del grupo `web` (registrado en `app/Http/Kernel.php:41`). No viene del servidor ni de un plugin.

**Enfoque elegido:** en lugar de modificar la página pública detectando iframe (plan original), se creó una **vista separada** `/grillaembebida` — copia recortada de la grilla, solo visualización. La página pública `/grid-actividades` no se tocó y conserva su `DENY`.

Dentro de un iframe cross-site los navegadores bloquean las cookies de terceros y los POST fallarían con 419 (Safari, Chrome incógnito, etc.), por eso la vista embebida no tiene lookup por email ni modal de inscripción: **"Inscribirme" y "Más info" abren milarepa.com.ar en pestaña nueva** (`window.open(route('grid-actividades.show-public', id), '_blank', 'noopener')`), donde sesión/CSRF/MercadoPago funcionan.

## Qué se implementó

### 1. `config/embed.php` (nuevo)

- `frame_ancestors`: orígenes que pueden embeber, configurable por `.env` (`EMBED_FRAME_ANCESTORS`, ver `.env.example`). Default: `https://meditarenargentina.org https://www.meditarenargentina.org`.
- `routes`: nombres de rutas embebibles. Hoy solo `grillaembebida`.

### 2. `app/Http/Middleware/SecurityHeaders.php`

Para las rutas listadas en `config('embed.routes')`:
- **NO** envía `X-Frame-Options` (el valor `ALLOW-FROM` está obsoleto; no existe forma de permitir un origen específico con ese header).
- Envía `Content-Security-Policy: frame-ancestors 'self' <orígenes>` — el mecanismo moderno; los navegadores que lo soportan lo priorizan sobre X-Frame-Options.

Para el resto de las rutas todo sigue igual (`DENY`). No reemplaza la tarea CSP completa 3.1b de SECURITY_AUDIT.md.

### 3. Ruta y controller

- `routes/web.php`: `GET /grillaembebida` → nombre `grillaembebida` → `GridActividadesController@grillaEmbebida`.
- `GridActividadesController`: se extrajo `actividadesParaGrilla()` (relaciones eager + `fecha_inicio_formateada`), compartido entre `index()` y `grillaEmbebida()`. El método embebible solo pasa `actividades` y `gridVariante` (sin datos de sesión ni catálogos; sin override `?grid=`, que requiere admin logueado).

### 4. `resources/js/Pages/GridActividades/GrillaEmbebida.vue` (nueva)

Copia recortada de `Index.vue`:
- Sin `AppLayout`, sin título h1, fondo blanco — la página de WordPress aporta su propio contexto.
- Reutiliza `ActividadCardGrid1/2`, `useActividadHelpers`, el `DataView` **sin paginación** (se muestran siempre todas las actividades; el alto del iframe se ajusta solo) y el modal de mapa (Google `output=embed` funciona anidado en iframe). Las cards se ven idénticas a la grilla pública (incluida la imagen de fondo del frente).
- Eliminado: panel de lookup por email, auto-lookup de `onMounted`, `InscripcionModoDialog` y todo el flujo de inscripción/login. Los precios se muestran sin descuento; el descuento se resuelve al continuar en la pestaña nueva.

### 5. Tests — `tests/Feature/Security/SecurityHeadersTest.php`

- `/welcome` → `DENY` (existente, sin cambios).
- `/grid-actividades` → sigue `DENY` (garantiza que la página pública quedó intacta).
- `/grillaembebida` → sin `X-Frame-Options`, con `Content-Security-Policy: frame-ancestors ...`, resto de headers presentes, y componente Inertia `GridActividades/GrillaEmbebida` con sus props.

## Cambio necesario en WordPress

El iframe debe apuntar a la **URL nueva** y usar el script de auto-alto (la vista embebida publica su altura real por `postMessage`, así el iframe se ajusta al contenido y no muestra scroll). Pegar todo en un bloque "HTML personalizado":

```html
<iframe id="grilla-milarepa" src="https://milarepa.com.ar/grillaembebida"
        width="100%" height="1200" scrolling="no"
        style="border:0; width:100%; display:block;"></iframe>
<script>
window.addEventListener('message', function (e) {
  if (e.origin !== 'https://milarepa.com.ar') return;
  if (e.data && e.data.type === 'grillaembebida:height') {
    document.getElementById('grilla-milarepa').style.height = e.data.height + 'px';
  }
});
</script>
```

Notas:
- El `height="1200"` es solo el alto inicial mientras carga; después lo gobierna el script.
- `scrolling="no"` elimina la barra de scroll del iframe.
- El chequeo de `e.origin` evita que otra página pueda manipular el alto.
- La altura se recalcula sola al girar cards o cargar imágenes (ResizeObserver en la vista embebida).

(La URL anterior `/grid-actividades` seguirá bloqueada dentro de iframes, a propósito.)

## Verificación

1. `php artisan test --filter=SecurityHeadersTest` — pasan los tres tests.
2. Local (Laragon): `curl -I http://milarepa.test/grillaembebida` → sin `X-Frame-Options`, con `Content-Security-Policy: frame-ancestors ...`; `curl -I http://milarepa.test/grid-actividades` y `/welcome` → siguen `X-Frame-Options: DENY`.
3. Probar el modo embebido end-to-end: crear un HTML de prueba con `<iframe src="http://milarepa.test/grillaembebida">`, servirlo en otro puerto (`php -S localhost:9999`), y setear `EMBED_FRAME_ANCESTORS=http://localhost:9999` en `.env` local. Verificar: la grilla se ve sin panel de email ni título y muestra todas las actividades sin paginación, "Inscribirme" y "Más info" abren pestaña nueva a `/grid-actividades/{id}/public`, el modal de mapa funciona y el iframe ajusta su alto solo (sin scroll interno).
4. `npm run build` sin errores.

## Notas de despliegue (producción)

- Correr `php artisan config:cache` tras el deploy (nueva config `embed.php`).
- Verificar con `curl -I https://milarepa.com.ar/grillaembebida` que el header realmente llega; si el hosting agregara su propio `X-Frame-Options` a nivel servidor, habría que quitarlo también ahí (en el repo no hay nada de eso, pero conviene confirmar).
- Actualizar el `src` del iframe en la página de WordPress a `https://milarepa.com.ar/grillaembebida`.

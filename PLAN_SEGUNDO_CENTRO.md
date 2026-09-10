# Instalar Milarepa en un segundo centro

> Estado al 2026-09-10: **Fases A, B y C hechas**. La app está en producción en
> **https://milarepa.meditarenzonanorte.org** con su base despersonalizada y su admin creado.
> La Fase A está **sin commitear y sin desplegar a Milarepa** (ver el prerrequisito del `.env`).
> Los detalles del servidor nuevo (docroot por symlink, `exec`/`proc_open` deshabilitados) están
> en la memoria del proyecto, no acá.
>
> El centro nuevo es **Meditar en Zona Norte** (`milarepa.meditarenzonanorte.org`), en su propio
> Hostinger. También es Kadampa, así que el vocabulario Tharpa/Tarjetas Kadampa **no se toca**.
>
> **Corrección al plan original**: "Milarepa" es el nombre del *sistema de registro*, no del centro.
> No hay que sacarlo del footer, de la home, del login ni de la firma de los mails: es correcto en
> las dos instalaciones. Sólo se parametriza lo que es **de cada centro**: su nombre como entidad,
> sus URLs y sus credenciales.

## Contexto

Hay que poner la app a funcionar para **otro centro de meditación**, en el hosting propio de ese centro (también Hostinger). Decisiones ya tomadas:

- **Instalación separada, un solo repo.** Cada centro con su dominio, su base y su hosting; el código es uno solo, **sin fork**.
- **La base se clona desde Milarepa y se despersonaliza**, conservando catálogos, plantillas de actividades/membresías y la estructura de medios de pago.
- Los únicos cambios específicos previstos hoy son **estéticos, en las dos páginas públicas** (clases y grilla de cursos), para que peguen con la web del centro nuevo.

### Por qué NO forkear

- El repo lleva **90 commits en los últimos 90 días** (48 solo en agosto). Un fork diverge en semanas y cada fix habría que cherry-pickearlo a mano, para siempre.
- La diferencia pedida es **cosmética en 2 vistas**, y la app **ya tiene el mecanismo** para eso: `grid_actividades_variante` en [GridActividadesController.php:69](app/Http/Controllers/GridActividadesController.php#L69) — clave en `configuraciones_sistema`, editable desde la UI de [Paginas/Configuracion.vue](resources/js/Pages/Paginas/Configuracion.vue), con override `?grid=2` para que un admin previsualice sin cambiar la config.
- La app **ya es multi-entidad**: `entidades` guarda nombre, dirección, teléfonos, redes y logo, y [AppServiceProvider.php:52](app/Providers/AppServiceProvider.php#L52) comparte `entidad_principal` por Inertia **en todas las páginas**. La identidad del centro ya es un dato, no código.

Lo que hoy impide instalar en otro centro sin tocar código es una lista **acotada y conocida** de textos y URLs hardcodeadas. Fase A las elimina, y eso beneficia a los dos centros.

---

## Fase A — Sacar del código lo que es de cada centro ✔ hecho

Lo que se hizo (8 archivos de código + 2 de tests + 1 migración, suite en 243 verdes y los
4 rojos preexistentes de `ImportarMultievento`):

| Cambio | Por qué |
|---|---|
| `config/embed.php`: `EMBED_FRAME_ANCESTORS` sin default | el default traía `meditarenargentina.org`, la web de Milarepa |
| `config/multievento.php`: `MULTIEVENTO_SHEET_URL` sin default | el default era una planilla concreta de Milarepa |
| [SecurityHeaders.php](app/Http/Middleware/SecurityHeaders.php): `trim()` del CSP | sin orígenes el header salía con un espacio colgando |
| [ImportarMultieventoService.php](app/Services/ImportarMultieventoService.php) | error claro (“definí MULTIEVENTO_SHEET_URL”) en vez de fallar al extraer el ID |
| `EnvioActividadesOnlineController` y `EmailPreviewController` | `https://milarepa.com.ar/actividades-online` → `route('paginas.actividades-online')` |
| `informacion_membresias.blade.php` | “Centro de Meditación Kadampa Argentina” → nombre de la entidad principal |
| `envio_Actividades_online.blade.php` | “Centro Kadampa de Palermo” → entidad principal; y la URL de fallback por `route()` |
| [UserSeeder.php](database/seeders/UserSeeder.php) | ya no crea usuarios con mails reales y contraseñas `12345678`/`editor`: los pide por `.env` (`SEED_ADMIN_*`) o por consola, y si no hay, no crea nada |
| Migración `add_spotify_uri_to_entidades_table` | la columna existe en la base de Milarepa pero **nunca tuvo migración**; guardada con `hasColumn` |
| `SecurityHeadersTest` | declara sus propios orígenes en vez de los de Milarepa, + test nuevo del default seguro |

Lo que **NO** se tocó, a propósito: el footer, la home, el login, `LogoMilarepa.vue`, la firma
“Milarepa - Sistema de Inscripciones” de los mails y el bloque de la Nueva Tradición Kadampa.
Es el nombre del sistema y la tradición de ambos centros.

### ⚠ Prerrequisito del deploy de Fase A a Milarepa

El `.env` de producción de Milarepa **no define** `EMBED_FRAME_ANCESTORS` ni `MULTIEVENTO_SHEET_URL`:
vive de los defaults que esta fase vació. Si se sube el código sin tocar antes el `.env`, la grilla
embebida en meditarenargentina.org deja de cargar (queda `frame-ancestors 'self'`) y el importador
multievento se queda sin planilla. **Primero el `.env`, después el código, después `config:cache`.**

```
EMBED_FRAME_ANCESTORS="https://meditarenargentina.org https://www.meditarenargentina.org"
MULTIEVENTO_SHEET_URL="https://docs.google.com/spreadsheets/d/15y4E50wO835jnlvLCMySwgTcEPeU5OPqbMeLJUpyg5A/edit?usp=sharing"
```

### Pendiente de Fase A: la variante de aspecto de las páginas públicas

No bloquea la instalación y se hace cuando Zona Norte pase su diseño. El molde ya existe:
`grid_actividades_variante` ([GridActividadesController.php:69](app/Http/Controllers/GridActividadesController.php#L69)),
una clave de `configuraciones_sistema` editable desde [Paginas/Configuracion.vue](resources/js/Pages/Paginas/Configuracion.vue)
con override `?grid=2` para previsualizar. Se replica como `clases_variante` para
[Paginas/Clases.vue](resources/js/Pages/Paginas/Clases.vue).

---

## Fase B — La base del centro nuevo: clonar y despersonalizar

La base de producción son **6,34 MB / 106 tablas** (`u302133020_milarepa`), más **95 MB** en `storage/app/public` (`comprobantes`, `img`, `profile-photos`). Clonar es barato; lo caro sería recrear a mano los catálogos, que **casi ninguno tiene seeder** (`paises`, `provincias`, `municipios`, `barrios`, `localidades`, `sexos`, `tipos_actividad`, `modalidades`, `estados_actividad`, `disponibilidades`, `programa_estudios`). Ese es el argumento real a favor de clonar.

### B1. Comando de despersonalización (nuevo, versionado en el repo)

Crear `app/Console/Commands/PrepararCentroNuevo.php` → `php artisan centro:preparar`. Que viva en el repo y no sea un SQL suelto es lo que lo hace auditable, repetible y testeable.

**Guardas obligatorias** (esto borra datos; el accidente ya pasó una vez, ver `SECURITY_AUDIT.md:416`):

- Imprime `DB::getDatabaseName()` y los conteos, y exige `--base=<nombre>` que **coincida exacto** con la base conectada.
- Aborta si el nombre contiene `u302133020_milarepa` (la base de producción de Milarepa).
- `--dry-run` por default: sin `--ejecutar` solo lista lo que borraría.

**Qué BORRA** (PII y transaccional del centro actual):

- Personas y sesión: `users` (salvo el admin nuevo), `model_has_roles`, `model_has_permissions`, `guest_users`, `invitados` + `invitado_*`, `sessions`, `password_reset_tokens`, `personal_access_tokens`.
- Movimiento: `inscripciones`, `inscripciones_clases`, `inscripcion_clase_items`, `inscripcion_comida`, `cobros`, `cobro_comprobantes`, `estado_cuenta_membresias`, `membresia_usuario`, `asistencias`, `ventas`, `venta_pos`, `venta_pos_items`, `prestamos_anexos`, `devoluciones_anexos`, `historico_pedidos_libros`.
- Operación del centro actual: `envios_mails`, `tickets`, `multievento_mapeos`, `grabaciones`, `links_grabacion`, `programa_grabacion`, `streams`, `links` (links de Zoom/streaming de Milarepa), `novedades`, `imagenes`, `versiones`, `failed_jobs`.
- **Personas que no son usuarios**: `maestros` (36) y `coordinadores` (17) son **nombres reales** de Milarepa. Se borran, y con ellos `actividad_maestro`, `actividad_coordinador`, `clase_maestro`, `clase_coordinador`.

**Qué CONSERVA:**

- Catálogos geográficos y de sistema: `paises`, `provincias`, `municipios`, `barrios`, `localidades`, `sexos`, `monedas`, `estados_actividad`, `estados_ticket`, `tipos_actividad`, `modalidades`, `disponibilidades`, `comidas`, `transportes`, `programas`, `programa_estudios`, `categorias_tienda`.
- Permisos y roles: `roles`, `permissions`, `role_has_permissions` — **intactos**, es lo que hace que la app funcione.
- **`migrations` intacta** (230 filas): es lo que hace que en el host nuevo `php artisan migrate` diga "Nothing to migrate" y que los deploys futuros funcionen.
- Plantillas: `actividades` (59) + `actividad_comida` / `actividad_hospedaje` / `actividad_transporte` / `actividad_metodo_pago`, `clases`, `ciclos`, `membresias`, `esquema_precios`, `esquema_descuentos`, `esquema_precio_membresias`, `esquema_descuento_membresias`, `precio_grupos`, `precio_grupo_membresias`, `lugares`, `lugares_hospedaje`, `hospedajes`, `descripciones`, `paginas_actividades_online`, `oraciones_cantadas`, `frases_de_dharma`, `libros` y `inventario_entidad_*`.
- `email_plantillas` y `email_envio_configuraciones` (estructura), con destinatarios vaciados.

**Qué RESETEA:**

- `entidades`: borrar las 13 filas y crear **una** con `entidad_principal = true` y los datos del centro nuevo. Ojo: `MembresiaSeeder` asume `entidad_id = 1`, así que hay que repuntar los `entidad_id` de lo que se conserva a la entidad nueva.
- `metodos_pago` (11) y `botones_pago` (38): se conserva la estructura pero se **vacían `descripcion` y `link`**, que es donde viven CBU/alias y links de pago reales de Milarepa.
- `configuraciones_sistema`: dejar solo las claves con valores neutros.
- Crear el usuario admin del centro nuevo con password pedida por prompt (nunca hardcodeada).

### B2. Archivos, no solo base

`storage/app/public/comprobantes` son **comprobantes de pago reales** y `profile-photos` son fotos de personas. **No se copian.** Se recrea la estructura vacía y el symlink `public_html/storage`. De `img` se copia solo lo que sea neutro (o nada, y el centro nuevo sube lo suyo).

### B3. Ensayo local antes de tocar nada

Todo esto se prueba primero en Laragon sobre una base `milarepa_centro_nuevo` copiada del dump, con la app local apuntando ahí. Recién cuando el resultado se ve bien en el navegador se sube.

---

## Fase C — Instalación en el Hostinger del centro nuevo

Aplica la skill `deploy-hostinger` (mismo proveedor), adaptando cuenta y dominio:

1. Acceso SSH del centro nuevo + alias en `~/.ssh/config`, igual que `agendaflex`.
2. Estructura: `~/domains/<dominio>/<app>` para el código y `public_html` como docroot. **Atención al split documentado**: el build de Vite tiene que quedar en **los dos lados** (`<app>/public/build` para que Laravel lea el manifest, y `public_html/build` para que el web sirva los assets), o los assets dan 404.
3. `deploy/README.md` y `deploy/public_html/index.php` tienen rutas de `milarepa.com.ar` hardcodeadas: adaptarlas.
4. `.env` nuevo y completo: `APP_NAME`, `APP_URL`, `APP_KEY` (**generar una nueva**, no copiar la de Milarepa), DB, `MAIL_*`, `MP_ACCESS_TOKEN`/`MP_PUBLIC_KEY`/`MP_WEBHOOK_SECRET` de la cuenta MercadoPago del centro nuevo, `GOOGLE_*` (proyecto propio con el callback del dominio nuevo), `EMBED_FRAME_ANCESTORS` y `MULTIEVENTO_*`.
5. Verificar la versión de PHP del hosting nuevo y usar el binario que coincida (en Milarepa: `/opt/alt/php84/usr/bin/php`).
6. Cron de hPanel para el scheduler. **Sin `cd ... &&`**, que el ejecutor de hPanel no soporta.
7. Webhook de MercadoPago apuntando a `https://<dominio-nuevo>/webhooks/mercadopago`.

---

## Verificación

1. **Local, antes de subir**: `composer test:regression` sobre la base de test de Milarepa (los 4 fallos de `ImportarMultieventoTest` son preexistentes). Los cambios de Fase A no deben mover ningún otro test.
2. **Fase A no cambia Milarepa**: con la config por default, comparar a ojo home, footer, login, grilla de cursos y página de clases contra producción. El default de cada clave nueva es el texto/variante actual.
3. **Base despersonalizada**: correr `centro:preparar --dry-run`, revisar la lista, ejecutar, y después verificar por SQL que `users`, `inscripciones`, `cobros`, `membresia_usuario` y `estado_cuenta_membresias` están en 0 y que `paises`/`provincias`/`permissions` siguen poblados. Grep del dump resultante por `@gmail.com` y por nombres de maestros: debe dar vacío.
4. **En el host nuevo**: `php artisan migrate` → "Nothing to migrate". Login con el admin nuevo. Alta de una actividad de prueba, inscripción de prueba y un pago de prueba en el sandbox de MercadoPago.
5. **Milarepa sigue igual**: después de desplegar Fase A a producción, revisar la grilla pública y un mail de inscripción.

---

## Riesgos y decisiones abiertas

- **Vocabulario Kadampa/Tharpa**: `info_tarjetas_kadampa` es una **columna real** en `guest_users` y `membresia_usuario`, y "Tharpa" nombra el módulo de tienda (`ProductoTharpaService`, ~40 archivos). Si el centro nuevo también es Kadampa, no se toca nada. Si no lo es, se renombran **solo los labels visibles**, nunca las columnas.
- **`entidad_principal` es un singleton implícito**: 40+ lugares hacen `Entidad::where('entidad_principal', true)->first()` y no hay índice único. Con una instalación por centro esto es correcto; es lo que habría que rehacer si alguna vez se unifica todo en una sola app.
- **Divergencia**: la disciplina que sostiene el "sin fork" es que **toda** diferencia entre centros viva en `.env`, en `entidades` o en `configuraciones_sistema`. La primera vez que se edite un `.vue` "solo para este centro" empieza el fork de hecho.
- **Fase A toca producción de Milarepa.** Conviene desplegarla sola, verificarla, y recién después arrancar la instalación nueva.

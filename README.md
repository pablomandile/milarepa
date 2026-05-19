# Milarepa — Sistema de Inscripciones

Sistema de gestión integral para un centro de meditación budista de la tradición Kadampa. Cubre actividades públicas (cursos/retiros), inscripciones, membresías (Tarjetas Kadampa), clases recurrentes, biblioteca (tharpa) con inventario y préstamos entre sedes, oraciones cantadas, pagos con comprobantes y comunicaciones por email.

---

## Stack

- **PHP** 8.1+ / **Laravel** 10.10
- **Inertia.js** 2.x (SPA renderizada desde controladores Laravel)
- **Vue** 3.2 + **PrimeVue** 3.53 + Tailwind 3.1 + PrimeFlex
- **MySQL** 8.x
- **Jetstream + Fortify + Sanctum** (auth + 2FA)
- **Spatie Permission** 6.4 (roles/permisos)
- **Ziggy** (nombres de ruta en JS)
- **Bacon QR Code** (tickets de asistencia)

---

## Requisitos

- PHP 8.1+ con extensiones standard (mbstring, openssl, pdo_mysql, fileinfo, gd)
- Composer 2.x
- Node.js 18+ / npm
- MySQL 8.x
- Servidor web (laragon en desarrollo, Apache/Nginx en producción)

---

## Setup (desarrollo)

```bash
# 1. Clonar e instalar dependencias
composer install
npm install

# 2. Configurar entorno
cp .env.example .env
php artisan key:generate

# 3. Editar .env con:
#    - APP_ENV=local
#    - APP_DEBUG=true
#    - LOG_LEVEL=debug
#    - DB y SMTP propios
# (`.env.example` viene con valores seguros para producción por default —
#  para desarrollo hay que aflojarlos manualmente)

# 4. Crear BD y migrar
php artisan migrate
php artisan db:seed   # Roles iniciales (admin, editor, asistant)

# 5. Storage symlink (uploads de imágenes/comprobantes)
php artisan storage:link

# 6. Compilar assets (dev)
npm run dev

# Build producción
npm run build
```

---

## Deploy a producción

Antes de pushear a producción, validar que el `.env` del servidor cumple las siguientes condiciones:

```bash
grep -q '^APP_ENV=production' .env || { echo "ABORT: APP_ENV no es production"; exit 1; }
grep -q '^APP_DEBUG=false'    .env || { echo "ABORT: APP_DEBUG no es false"; exit 1; }
grep -q '^APP_KEY=base64:'    .env || { echo "ABORT: APP_KEY vacío o inválido"; exit 1; }
```

Recordatorios:
- El `.env` de producción **no se copia desde el repo** — vive en el servidor y se gestiona manualmente o vía secret manager.
- Tras cambios en `RoleSeeder`: correr `php artisan db:seed --class=RoleSeeder --force` (idempotente para permisos, destructivo para `syncPermissions` de roles — coordinar ventana).
- Tras cambios en assets: `npm ci && npm run build` en el servidor.

---

## Comandos Artisan custom

| Comando | Descripción |
|---|---|
| `php artisan inscripciones:enviar-emails` | Envía confirmaciones/grabaciones por email |
| `php artisan inscripciones:reporte-semanal` | Reporte semanal a la entidad principal |
| `php artisan membresias:renovar-mensual` | Renovación automática mensual de membresías |
| `php artisan inscripciones:debug-email` | Debug de plantillas de email |

---

## Estructura del proyecto

```
app/
├── Console/Commands/        # 4 comandos programados
├── Http/
│   ├── Controllers/         # ~76 controllers (CRUD Inertia)
│   └── Requests/            # Form Requests
├── Models/                  # 64 modelos Eloquent
├── Services/                # Lógica de negocio extraída
└── Mail/                    # InscripcionConfirmada + plantillas
database/
├── migrations/              # 149 migraciones (deuda técnica — ver ARCHITECTURE.md)
└── seeders/                 # Roles, permisos, datos base
resources/
├── js/
│   ├── Pages/               # ~100 páginas Inertia
│   ├── Components/          # Componentes + Formularios/ (60+)
│   └── Layouts/AppLayout.vue
└── views/emails/            # Plantillas Blade de email
routes/
├── web.php                  # ~400 líneas, mayoría con middleware auth
└── api.php                  # GET /user (Sanctum)
```

---

## Roles del sistema

- **admin**: acceso total
- **editor**: todo excepto gestión de roles/usuarios
- **asistant**: lectura de actividades, CRUD de tickets, lectura de novedades

Detalles en [BUSINESS_RULES.md](BUSINESS_RULES.md#8-roles-y-permisos).

---

## Documentación

- [CONTEXTO.md](CONTEXTO.md) — Snapshot inicial del análisis del repo
- [ARCHITECTURE.md](ARCHITECTURE.md) — Arquitectura, decisiones, deuda técnica, riesgos
- [BUSINESS_RULES.md](BUSINESS_RULES.md) — Reglas de negocio extraídas del código

---

## Avisos importantes

> **Seguridad**: el archivo `.env` versionado contiene credenciales SMTP en texto plano. Antes de exponer el repo a más colaboradores, rotar la contraseña de aplicación de Gmail y mover a un sistema de secrets (Vault, Doppler, o `.env.local` ignorado por git). Ver [ARCHITECTURE.md — Riesgos](ARCHITECTURE.md#9-riesgos-técnicos).

> **Versiones desactualizadas**: PrimeVue 3.x y `@primevue/themes` 4.x conviven hoy y son potencialmente incompatibles. Vue 3.2 está varias versiones menores por detrás. Antes de upgrades mayores, probar en branch separada.

> **149 migraciones** (más que tablas — varias son `add_X_to_Y` y `drop_X_from_Y`). Hay 7 tablas en BD sin migración `create_X_table.php` correspondiente. Ver [ARCHITECTURE.md — Deuda técnica](ARCHITECTURE.md#8-deuda-técnica).

---

## Tests

### Setup inicial (una vez)

Los tests corren contra una BD MySQL aislada (`milarepa_testing`), nunca tocan tu BD de desarrollo:

```bash
# 1. Crear BD de testing
mysql -u root -e "CREATE DATABASE milarepa_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Copiar .env como base de configuración para tests
cp .env .env.testing

# 3. Editar .env.testing y cambiar:
#    APP_ENV=testing
#    DB_DATABASE=milarepa_testing
#    LOG_LEVEL=warning

# 4. Generar schema dump desde milarepa (lo lee migrate:fresh)
mysqldump -u root --no-data milarepa > database/schema/mysql-schema.sql

# 5. Cargar schema + sembrar roles en milarepa_testing
mysql -u root milarepa_testing < database/schema/mysql-schema.sql
mysql -u root -e "INSERT INTO milarepa_testing.migrations (migration, batch) SELECT migration, batch FROM milarepa.migrations;"
php artisan db:seed --env=testing --class=RoleSeeder --force
```

### Correr tests

```bash
php artisan test                          # toda la suite
php artisan test tests/Feature/Security/  # solo los de seguridad (14 tests, ~1s)
```

### Tests de seguridad (tarea 4.2)

En `tests/Feature/Security/` hay 6 archivos con **14 tests** que cubren los fixes de la auditoría OWASP — evitan regresiones si alguien cambia el código sin querer:

| Test | Cubre |
|---|---|
| `SecurityHeadersTest` | 4 headers HTTP de seguridad (tarea 3.1) |
| `LookupEmailThrottleTest` | throttle:5,1 en lookup-email (tarea 2.1) |
| `AdminEndpointPermissionTest` | permission:read usuarios (tarea 2.2) |
| `EmailPreviewIdorTest` | IDOR cerrado en /email-preview/{tipo}/{id} (tarea 1.2) |
| `GridInscripcionSignedTest` | middleware `signed` en grid-actividades/inscripcion (tarea 1.3) |
| `LookupTokenIdorTest` | helper `UserLookupToken` (cifrado, TTL, tampering) (tarea 2.1b) |

Sin CI/CD configurado todavía — los tests se corren a mano antes de cada commit importante.

---

## Autor / Mantenimiento

Pablo Mandile — pablo.mandile@gmail.com

Proyecto activo desde 2024. Branch principal: `main`.

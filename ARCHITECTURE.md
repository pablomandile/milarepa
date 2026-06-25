# Arquitectura — Milarepa

Documento técnico de arquitectura, decisiones, dependencias y deuda. Complementa [README.md](README.md) (setup) y [BUSINESS_RULES.md](BUSINESS_RULES.md) (dominio).

---

## 1. Visión general

Aplicación web monolítica con **renderizado server-side de páginas Vue vía Inertia.js**. No es API-only ni Blade tradicional: los controladores Laravel devuelven respuestas `Inertia::render('Page', $props)` que el cliente convierte en transiciones SPA sobre Vue 3.

```
┌──────────────────────────────────────────────────────────────┐
│  Browser (Vue 3 + PrimeVue + Tailwind)                       │
│  ↕ Inertia bridge (XHR con shared props)                     │
│  Laravel routes/web.php  →  Controllers  →  Models           │
│  ↕                                                            │
│  MySQL (80 tablas) + Storage local (public/storage)          │
└──────────────────────────────────────────────────────────────┘
```

Sin frontend separado, sin colas, sin caché Redis activo (`QUEUE_CONNECTION=sync`).

---

## 2. Stack y dependencias

### Backend (`composer.json`)

| Paquete | Versión | Rol |
|---|---|---|
| `laravel/framework` | ^10.10 | Core |
| `laravel/jetstream` | ^4.3 | Scaffolding auth + 2FA |
| `laravel/sanctum` | ^3.3 | Auth de sesión + API tokens |
| `inertiajs/inertia-laravel` | ^2.0 | Bridge SSR Inertia |
| `spatie/laravel-permission` | ^6.4 | RBAC |
| `tightenco/ziggy` | ^2.3 | Helper `route()` en JS |
| `guzzlehttp/guzzle` | ^7.2 | HTTP client |

> Sin paquetes para pagos (Stripe/MercadoPago), colas externas o storage S3. La lógica de "botones de pago" es custom — apunta a procesadores externos por URL.

### Frontend (`package.json`)

| Paquete | Versión instalada | Latest mayor | Estado |
|---|---|---|---|
| `vue` | 3.2.31 | 3.5.x | Atrasado |
| `primevue` | 3.53.1 | 4.x | **1 mayor atrás** |
| `@primevue/themes` | 4.2.4 | — | Incompatible con primevue 3 |
| `tailwindcss` | 3.1.0 | 4.x | 1 mayor atrás |
| `@inertiajs/vue3` | 1.0.0 | 1.x | Actual |
| `vite` | 5.0.0 | 5.x | Actual |
| `sweetalert2` | 11.14.5 | 11.x | Actual |

> Inconsistencia clave: `primevue@3` con `@primevue/themes@4`. Auditar si `@primevue/themes` realmente se está usando o quedó como dependencia muerta.

---

## 3. Capas y módulos

### Backend

```
app/
├── Http/Controllers/   # 76 controllers, CRUD-heavy, devuelven Inertia
├── Models/             # 64 modelos Eloquent (todos con $fillable)
├── Services/           # Lógica extraída — el más relevante: EmailInscripcionService,
│                       #   ReporteInscripcionesPorActividadService
├── Console/Commands/   # 4 comandos: RenovarMembresiasMensual,
│                       #   EnviarReporteSemanalInscripcionesActividad,
│                       #   EnviarEmailsInscripciones, DebugEmailInscripcion
├── Mail/               # InscripcionConfirmada
└── Http/Requests/      # Form Requests (validación)
```

### Frontend

```
resources/js/
├── app.js              # Bootstrap: Inertia createApp + PrimeVue (locale ES, ToastService)
├── Pages/              # ~100 páginas Inertia organizadas por dominio
├── Components/
│   ├── Formularios/    # 60+ forms (uno por entidad)
│   └── ...             # ImageUploader, Dialog, InputError, NavLink, Banner
└── Layouts/AppLayout.vue  # Navbar con dropdowns por categoría
```

### Módulos funcionales

| Módulo | Páginas Vue principales | Controllers |
|---|---|---|
| **Actividades** | Actividades/Index, Edit, Create | ActividadesController, GridActividadesController |
| **Inscripciones a actividades** | Inscripciones/*, EstadoInscripciones | InscripcionesController, EstadoInscripcionesController |
| **Clases y ciclos** | Clases/*, Ciclos/*, InscripcionesClases | ClasesController, InscripcionesClasesController |
| **Membresías** | Membresias/*, MembresiasGestion, EstadoCuentaMembresias | MembresiasController, MembresiasGestionController, EstadoCuentaMembresiasController |
| **Biblioteca / Tharpa** | Libros, InventarioLibros, VentasLibros | LibrosController, InventarioLibrosController, VentasLibrosController |
| **Oraciones cantadas** | OracionesCantadas/* | OracionesCantadasController |
| **Pagos** | Monedas, Métodos, Esquemas, Botones | EsquemaPreciosController, BotonesPagoController, etc. |
| **Emails** | Plantillas, Histórico | EmailsController, EmailPreviewController |
| **Configuración** | Usuarios, Roles, Permisos, Sistema | UsuariosController, PaginasConfiguracionController |

---

## 4. Modelo de datos

- **84 tablas** físicas en MySQL (incluye `invitados` + 3 pivotes `invitado_*`).
- **65 modelos Eloquent** mapeados con `$fillable` (100% protegidos contra mass assignment).
- **41% de modelos sin `$casts`** explícito — campos boolean/decimal/array vienen como strings.

Entidades centrales:
- `Entidad` (sedes/centros físicos) — eje organizativo de inventario, ventas, préstamos, clases.
- `Actividad` (n:m con maestros, coordinadores, hospedajes, comidas, transportes, métodos de pago).
- `Inscripcion` y `InscripcionClase` (con `GuestUser` para no autenticados).
- `Invitado` (hijo de `Inscripcion`, hasta 10): acompañantes que el titular suma en el pago; pivotes
  `invitado_comida/_transporte/_hospedaje` para sus servicios. No confundir con `GuestUser`. Ver
  [BUSINESS_RULES.md §2.6](BUSINESS_RULES.md).
- `Membresia` + `MembresiaUsuario` + `EstadoCuentaMembresia`.
- `Libro` + `InventarioEntidadLibro` + `PrestamoAnexo` + `DevolucionAnexo` + `Venta`.
- `OracionCantada` con `configuracion_por_mes` (JSON) que sobrescribe el calendario base.

Diagrama relacional detallado en [BUSINESS_RULES.md](BUSINESS_RULES.md).

---

## 5. Autenticación y autorización

- **Sesión web** vía Jetstream/Fortify (login, registro, recuperación, 2FA opcional).
- **API tokens** vía Sanctum (sin uso significativo — `routes/api.php` casi vacío).
- **RBAC** con Spatie Permission. Tres roles: `admin`, `editor`, `asistant`. Permisos por entidad: `create/read/update/delete X`.
- Chequeos en frontend con `$page.props.user.permissions.includes('...')`.
- Chequeos en backend dispersos — algunos controllers carecen de `$this->authorize()`. Ver Riesgos.

---

## 6. Patrones observados

- **CRUD Inertia-driven**: cada módulo expone `index/create/store/edit/update/destroy` que devuelven `Inertia::render`. Los formularios emiten `@submitted` al parent.
- **Validación server-side** vía Form Requests (`app/Http/Requests/`); errores se exponen automáticamente como `errors` en props de Inertia y se consumen con `<InputError />`.
- **DataTable PrimeVue eager** (paginación cliente sobre array completo) — patrón dominante tras la corrección de bugs de paginación de mayo 2026.
- **Auditoría**: inscripciones registran `created_at` + `auditor_user_id`.
- **Soft deletes**: presente en `EstadoCuentaMembresia` (otros modelos no lo usan).
- **Confirmaciones destructivas** con SweetAlert2 (no con `Confirm` de PrimeVue).
- **QR firmado** con `URL::signedRoute` para asistencias.

---

## 7. Flujos críticos (resumen)

Ver [BUSINESS_RULES.md](BUSINESS_RULES.md) para detalle operativo.

1. **Inscripción pública a actividad**: usuario sin login → llena formulario en `/grid-actividades` → crea `GuestUser` (si no existe) o usa `User` existente → en la pantalla de pago puede sumar hasta 10 **invitados** (acompañantes, precio general, servicios propios) → registra `Inscripcion` + `Invitado`s en una transacción, con monto desglosado (incluye `monto_invitados`) → opcionalmente sube comprobante → recibe email de confirmación con QR firmado y el detalle de invitados.
2. **Inscripción a clase**: idem pero suma `articulos_tharpa` (descuenta `InventarioEntidadLibro`) y `articulos_tienda`.
3. **Renovación de membresía**: comando mensual programable expira los `EstadoCuentaMembresia` previos y crea uno nuevo con estado `ACTIVA`; si modalidad es `Suscripción`, marca pagado automáticamente.
4. **Reporte semanal**: cron envía resumen de inscripciones a la entidad principal o destinatario configurado.
5. **Asistencia con QR**: usuario escanea QR → valida firma URL → marca `Asistencia` y actualiza `inscripcion.asistencia = 'presente'`.

---

## 8. Deuda técnica

### Crítica

- **149 migraciones**, muchas son `add_X_to_Y_table` o `drop_X_from_Y_table`. **Más migraciones que tablas**. Auditoría cruzada con MySQL reveló **7 tablas sin migración `create` en filesystem** (sus migraciones originales fueron borradas/regeneradas):
  - `actividades` — tabla central
  - `inscripciones` — tabla central
  - `barrios`, `municipios`, `provincias` — geo
  - `guest_users`
  - `estados_actividad`
  
  En BD aún existen porque sus migraciones se ejecutaron antes de ser eliminadas del filesystem. Pendiente: regenerar `create` consolidados desde introspección o usar `php artisan schema:dump --prune`.

- **README.md original era el default de Laravel** (resuelto con este commit).

- **Ningún CI/CD**. Sin `.github/workflows` ni similar.

### Alta

- **Vistas Vue monolíticas**: `Inscripciones/Index.vue` (1041 LOC), `Actividades/Index.vue` (903 LOC), varias más > 600 LOC. Sin componentización.

- **Cobertura de tests ~5%**: 14 de 18 archivos son scaffolds de Jetstream sin lógica de negocio probada. Sólo `RenovarMembresiasMensualTest` es un test real.

- **Archivo duplicado** `EstadoInscripcionesController - copia.php` en el repo (backup manual).

- **`console.log` olvidados** en al menos 13 Edit.vue pese al commit `c79c63b` ("quito todos los consolelog").

### Media

- 41% de modelos sin `$casts` explícito → tipados implícitos (booleans como strings, JSON sin parse automático).
- Controladores con lógica de cálculo inline en lugar de Services (ver `GridActividadesController` con 1000+ LOC de queries y cálculos).
- Sin error tracking (Sentry, Bugsnag) ni APM.

---

## 9. Riesgos técnicos

| Riesgo | Severidad | Impacto |
|---|---|---|
| **Credenciales SMTP en `.env` versionado** | Crítica | Compromiso de cuenta Gmail; spam relay |
| **Rutas `/email-preview/{id}` públicas sin auth** | Crítica | IDOR — exposición de datos de inscripciones por enumeración de IDs |
| **`MembresiasGestionController::asignar/eliminar` sin `$this->authorize()` explícito** | Alta | Cualquier user autenticado podría modificar membresías ajenas si no hay middleware de rol en la ruta. Verificar `routes/web.php`. |
| **Upload sin renombrado seguro** en `GridActividadesController::uploadComprobante` | Alta | Posible colisión de nombres / traversal — usar `storeAs` con nombre generado |
| **Rate limiting ausente** en rutas públicas (`/grid-actividades/lookup-email`, `/grid-actividades/inscribir`) | Media | Enumeración de usuarios, spam de inscripciones |
| **`QUEUE_CONNECTION=sync`** en producción | Media | Envío de emails bloquea respuesta HTTP; reintentos sin garantía |
| **PrimeVue 3.x + themes 4.x** | Media | Comportamiento UI inconsistente bajo updates futuros |
| **Migraciones sin squash** | Baja | `migrate:fresh` lento; difícil de auditar |
| **APP_DEBUG=true en `.env.example`** | Baja | Riesgo si se copia tal cual a producción |

---

## 10. Decisiones / convenciones

- **Idioma del código**: español (modelos, controladores, rutas, columnas). No mezclar con inglés.
- **Paginación**: PrimeVue eager (cliente) sobre arrays planos del backend. **No usar `->paginate()` salvo necesidad explícita por volumen** — causó el bug histórico de "registros perdidos".
- **Confirmaciones destructivas**: SweetAlert2 (no PrimeVue Confirm), por consistencia visual.
- **Fechas en formularios**: ISO `YYYY-MM-DD` en backend, formato local en UI vía `toLocaleDateString('es-AR'/'es-ES')`.
- **Subida de archivos**: `Storage::disk('public')`, ruta servida con `storage:link`.

---

## 11. Sugerencias de mejora (priorizadas)

### Inmediatas (1-2 días)

1. **Rotar credenciales SMTP** y mover `.env` fuera del repo. Agregar `.env` a `.gitignore` si no está.
2. **Proteger `/email-preview/{id}`** con middleware `auth` + verificación de rol.
3. **Eliminar `EstadoInscripcionesController - copia.php`** (archivo de backup manual).
4. **Limpiar `console.log`** restantes en 13 Edit.vue.
5. **Aplicar rate limiting** (`throttle:60,1`) a `/grid-actividades/lookup-email` y endpoints públicos.

### Corto plazo (1-2 semanas)

6. **Consolidar migraciones**: regenerar 1 migración `create` por tabla con el schema final, o usar `php artisan schema:dump --prune`.
7. **Refactorizar `GridActividadesController`** y `InscripcionesController` extrayendo lógica a `Services/` (cálculo de monto, resolución de esquema de precio, descuentos por membresía).
8. **Configurar GitHub Actions**: lint (Pint), tests, build de assets en cada PR.
9. **Agregar `Sentry`** o equivalente para tracking de errores en producción.
10. **Mover envío de emails a queue**: cambiar `QUEUE_CONNECTION=database` y correr worker.

### Mediano plazo (1 mes)

11. **Tests E2E** para flujos críticos: inscripción pública, asignación de membresía, renovación mensual, venta de tharpa.
12. **Componentizar** las 5 Vue pages mayores a 700 LOC (extraer columnas de DataTable, formularios, modales).
13. **Upgrade PrimeVue 3 → 4** en branch separada (cambia API de PassThrough, theming, varios componentes).
14. **Documentar API interna** de Services (PHPDoc + ejemplos).

---

## 12. Glosario técnico

- **Tharpa**: literatura budista sagrada (no es marca interna). Determina el tratamiento del modelo `Libro`.
- **Entidad**: sede/centro físico. Eje organizativo.
- **Ciclo**: agrupador temporal de `Clase`s (e.g. "Ciclo Octubre 2025").
- **Esquema de precio/descuento**: tabla maestra de precios por (membresía × moneda) usada para calcular monto a pagar.
- **Botón de pago**: registro que apunta a URL externa de procesador (MercadoPago u otro).
- **Tarjeta Kadampa**: nombre comercial de la membresía.

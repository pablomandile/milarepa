# Contexto del proyecto Milarepa

> Documento de contexto generado tras explorar el repo. No es un plan de implementación — el usuario pidió armar contexto antes de trabajar en tareas puntuales.

## 1. Qué es

Sistema de gestión integral para un centro de meditación budista (tradición Kadampa / Milarepa). Cubre el ciclo completo: actividades públicas, inscripciones, membresías, biblioteca con inventario y préstamos entre sedes, oraciones cantadas, pagos con comprobantes, y comunicaciones por email.

## 2. Stack técnico

**Backend** (composer.json declara `^10.10`, aunque el usuario lo describe como Laravel 11):
- Laravel + **Inertia.js v2** (no es API-only — SPA con páginas Inertia renderizadas desde controladores)
- **Jetstream + Fortify + Sanctum** para auth, con 2FA habilitado
- **Spatie Permission v6.4** para roles/permisos (roles vistos: `admin`, `editor`, `asistant`)
- Ziggy para nombres de ruta en JS
- Bacon QR Code para tickets de asistencia

**Frontend**:
- Vue 3.2 + **PrimeVue 3.53** (tema `lara-light-blue`, locale ES custom)
- Tailwind 3.1 + PrimeFlex
- SweetAlert2 para confirmaciones destructivas
- Sin Pinia/Vuex — estado vía `$page.props` de Inertia (server-driven reactivity)
- Sin PassThrough en PrimeVue, estilos custom en CSS

## 3. Modelo de dominio (entidades clave)

| Entidad | Rol |
|---|---|
| **Entidad** | Sede/centro (logo, contactos, redes). Eje organizativo: el inventario, ventas, préstamos, clases se asocian a una entidad |
| **Actividad** | Cursos/retiros. Tiene tipo, descripción, programa, lugar, modalidades de pago, hospedaje/comida/transporte, maestros y coordinadores (n:m) |
| **Clase** | Sesiones recurrentes dentro de un **Ciclo**, con stream y maestro |
| **Inscripcion** | A una Actividad. Soporta usuario autenticado o `GuestUser`, comprobante de pago, QR firmado, asistencia. Puede llevar hasta 10 `Invitado`s |
| **Invitado** | Acompañante/familiar que el titular suma a su inscripción (no es usuario ni `GuestUser`). Paga precio general, elige servicios propios y tiene asistencia individual. Ver [BUSINESS_RULES §2.6](BUSINESS_RULES.md) |
| **LugarHospedaje** / **Hospedaje** | Lugar físico → acomodación (tipo de habitación con precio). El **cupo** es por actividad (`actividad_hospedaje.cantidad`); disponibilidad por conteo de reservas. Ver [BUSINESS_RULES §2.7](BUSINESS_RULES.md) |
| **InscripcionClase** | A una Clase. Tres montos desglosados: actividad + tharpa + tienda. JSON con artículos |
| **Membresia** | Tarjetas Kadampa con planes, esquema de precios y estado de cuenta. Estados: ACTIVA/INACTIVA/VENCIDA |
| **OracionCantada** | Práctica con periodicidad base + horario por día (`horarios_por_dia`) + `configuracion_por_mes` (JSON) que sobrescribe día/hora/días por mes |
| **Libro** + **InventarioEntidadLibro** | "Tharpa" = literatura budista sagrada. Inventario por entidad |
| **PrestamoAnexo** / **DevolucionAnexo** | Préstamo de libros entre entidades, ciclo cerrado por devolución |
| **Venta** | Venta de libros a entidad, con comprobante imagen, modo de pago, vendedor |
| **EsquemaPrecio** / **EsquemaDescuento** | Tarifación flexible vinculada a membresías |
| **Grabacion** + **LinkGrabacion** | Videos de actividades online |
| **BotonPago** | Botones de pago externos (presumiblemente MercadoPago u otro) |

65 modelos en total en [app/Models](app/Models).

## 4. Arquitectura backend

- **71 controladores** en [app/Http/Controllers](app/Http/Controllers). CRUD-heavy, Inertia-driven (renderizan páginas Vue con props).
- **routes/web.php** (~402 líneas) concentra la mayoría — públicas (`/grid-actividades`, `/calendario`, `/membresias/public`, `/email-preview/*`) y autenticadas con `verified`.
- **routes/api.php** prácticamente vacío (sólo `GET /user`).
- **Comandos Artisan** (4): `EnviarEmailsInscripciones`, `EnviarReporteSemanalInscripcionesActividad`, `RenovarMembresiasMensual`, `DebugEmailInscripcion`.
- **Mails**: `InscripcionConfirmada`. Plantillas Blade en [resources/views/emails](resources/views/emails) — incluye `reporte_semanal_inscripciones_actividad.blade.php`.
- Sin `Jobs/` ni `Notifications/` — los envíos son síncronos o vía comandos programados.
- 90+ migraciones en [database/migrations](database/migrations).

## 5. Arquitectura frontend

```
resources/js/
├── app.js              # Setup Inertia + PrimeVue (ToastService, ConfirmationService, locale ES)
├── Pages/              # 100+ páginas Inertia organizadas por dominio
├── Components/
│   ├── Formularios/    # 60+ forms domain-specific (ActividadForm, ClaseForm, etc.)
│   └── ...             # Base UI (Dialog, Buttons, Inputs, ImageUploader, Banner)
└── Layouts/AppLayout.vue   # Navbar con menús dropdown por categoría
```

**Patrones observados**:
- DataTable de PrimeVue con sortable/paginated, acciones inline con Font Awesome, confirmación destructiva con SweetAlert2
- Formularios en `FormSection` con `InputLabel`/`TextInput`/`InputError` (validación server-side via Inertia)
- Permisos chequeados con `$page.props.user.permissions`
- Subida de imágenes (comprobantes, logos) vía `ImageUploader`/`SingleImageUploader`

## 6. Menús y secciones (visión funcional)

Desde [AppLayout.vue](resources/js/Layouts/AppLayout.vue):
- **Gestión**: Entidades, Lugares, Maestros, Coordinadores, Membresías, Libros, Hospedajes, Transportes, Comidas, Imágenes
- **Actividades**: Cursos/Retiros, Clases, Oraciones Cantadas, Tipos, Ciclos, Descripciones, Programas, Modalidades, Disponibilidades, Streams, Grabaciones, Asistencias
- **Inscripciones**: Mis Inscripciones, Por Actividad, Por Clases, Estado
- **Membresías**: Tarjetas Kadampa, Estado de Cuenta, Gestión Usuarios
- **Pagos**: Monedas, Métodos, Esquemas (Precios/Descuentos), Botones, Exenciones
- **Páginas públicas**: Actividades Online, Calendario, Grilla de Cursos, Membresías Pública
- **Emails**: Gestión, Histórico, Envío Actividades Online, Plantillas
- **Configuración**: Usuarios, Roles, Permisos, Config General, Email
- **Ayuda**: Centro, Novedades, Versiones, Acerca de

## 7. Flujos clave

1. **Inscripción a actividad** (pública o autenticada) → opcional: sumar hasta 10 invitados (precio general + servicios propios) → al confirmar valida cupo de hospedaje (rechaza si está agotado) → comprobante de pago (imagen) → QR firmado → asistencia (del titular y de cada invitado). El admin edita la inscripción en un dialog que recalcula montos y respeta el cupo; borrar/editar libera la acomodación. El admin también puede **crear** una inscripción en nombre de otra persona (participante existente o nuevo) desde "Estado de inscripciones", reutilizando la pantalla de pago
2. **Inscripción a clase** → calcula tres montos (actividad + tharpa + tienda) → registra artículos en JSON
3. **Inventario tharpa** por entidad → ventas y préstamos entre sedes con devoluciones
4. **Membresías** → estado de cuenta, renovación mensual vía comando Artisan
5. **Reporte semanal** de inscripciones por actividad → email automático configurable (día/hora/destinatario)
6. **Oraciones cantadas** → calendarios personalizables por mes

## 8. Convenciones observadas

- **Idioma**: nombres de modelos, controladores, rutas y campos en español
- **Estilo Inertia**: páginas reciben props del controlador, formularios emiten `@submitted` al parent
- **Auditoría**: timestamps + auditor en inscripciones
- **Validación**: server-side (Laravel) → errores expuestos via `InputError` en cada form

## 9. Lagunas / cosas a confirmar cuando arranquemos

- Confirmar versión Laravel real (composer dice 10.x, usuario dice 11)
- Si hay tests (Pest/PHPUnit) — no se exploró carpeta `tests/`
- Si hay pipeline CI/CD o sólo deploy manual
- Estado de seeders de roles iniciales (no detectados en exploración rápida)
- Procesador de pago detrás de `BotonPago`

## 10. Cómo arrancar para tareas puntuales

Cuando vengan pedidos puntuales, los lugares más probables a tocar son:
- **Cambios en formularios** → `resources/js/Components/Formularios/{Entidad}Form.vue` + `{Entidad}Controller@store/update`
- **Nueva pantalla** → crear `resources/js/Pages/{Modulo}/Index.vue` + ruta en `web.php` + controller
- **Nuevo campo** → migración + modelo (`fillable`/`casts`) + form + controller validation + posibles vistas/listados
- **Cambio de email** → `resources/views/emails/*.blade.php` + posible Mail class en `app/Mail/`
- **Permisos** → tabla Spatie, chequeo en controller (middleware/policy) y en frontend (`$page.props.user.permissions`)

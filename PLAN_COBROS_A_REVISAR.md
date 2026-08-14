# Plan: cobro "a revisar" al subir comprobante + estado filtrable en Estado de inscripciones

> Trabajo en curso (2026-08-14). Este archivo es el plan aprobado + estado de avance para
> retomar la sesión. Copia de referencia del plan original:
> `C:\Users\pghm\.claude\plans\hice-cambios-en-la-composed-quill.md`

## Estado de avance

**✅ IMPLEMENTACIÓN COMPLETA (2026-08-14).** Todo lo listado abajo está hecho y verificado
en local: suite 144 verdes (+11 tests nuevos; los únicos fallos son los 4 preexistentes de
ImportarMultievento), `npm run build` OK, `cobros:migrar-staging` corrido en local
(2 comprobantes migrados, re-corrida idempotente en 0). **Sin commitear.**

**Checklist de deploy a producción** (Hostinger, PHP web 8.3 — alcanza, el lock exige ≥8.2):
1. Subir código + `public/build` (a `milarepa/public/build` Y `public_html/build`).
2. `/opt/alt/php83/usr/bin/php` + `composer install` (bump de nette/schema en el lock).
3. `php artisan migrate` (agrega `cobros.estado`).
4. `php artisan cobros:migrar-staging --dry-run` → revisar conteos → corrida real.

### Hecho ✅
1. **Entorno local migrado a PHP 8.4** (requisito de `mercadopago/dx-php` ≥8.2):
   - `c:\laragon\usr\laragon.ini` → `Version=php-8.4.21-Win32-vs17-x64`
   - `c:\laragon\etc\apache2\mod_php.conf` → dll + PHPIniDir del 8.4
   - PATH de usuario actualizado (8.1 → 8.4); Apache reiniciado y sitio respondiendo
   - `composer update nette/schema --with-all-dependencies` (bump mínimo: 1.3.0 topeaba en PHP 8.3) + vendor completo instalado (SDK MercadoPago incluido) → **composer.json/lock modificados, commitear junto con el resto**
2. **BD de test refrescada** desde `milarepa` (procedimiento de `tests/README.md`)
3. **Migración** `database/migrations/2026_08_14_000001_add_estado_to_cobros_table.php` (`cobros.estado` string(20) default `confirmado` + índice) — **ya corrida en `milarepa` y `milarepa_testing`**
4. **`app/Models/Cobro.php`**: constantes `ESTADO_CONFIRMADO`/`ESTADO_A_REVISAR`, `estado` en fillable, `$attributes` default, scopes `confirmados()`/`aRevisar()`
5. **`app/Models/Concerns/TieneCobros.php`**: `montoCobrado()` solo suma confirmados; helpers `cobrosARevisar()`/`tieneCobrosARevisar()`
6. **`app/Services/CobroService.php`**: passthrough de `estado` en `registrar()`; nuevos `agregarComprobantes()`, `registrarComprobanteARevisar()`, `confirmarORegistrar()`; blindaje `confirmados()` en `recalcularMembresia()`; nota sobre el `delete()` de `sincronizarMembresia()`
7. **Checkpoint de suite**: `composer test:regression` → 133 pasan, **4 fallos PREEXISTENTES** de `ImportarMultieventoTest` (asserts de conteos globales incompatibles con BD de test con datos reales — NO son de este trabajo; verificado con stash)

### Hecho también (era "pendiente") ✅
1. **Rewire de escritores del staging** (nadie más escribe `inscripcion_comprobantes`):
   - `app/Http/Controllers/InscripcionesController.php` `uploadComprobante()` (~L463): reemplazar `InscripcionComprobante::create` por `registrarComprobanteARevisar($inscripcion, $imagenId, $descripcion, origen: $esAdmin ? 'manual' : 'checkout', registradoPor: $esAdmin ? $user->id : null)`
   - `app/Http/Controllers/GridActividadesController.php` `finalizarPago()` — DOS bloques `InscripcionComprobante::create` (~L633-639 rama update y ~L696-702 rama create): ídem con `resolverComprobanteId($pago['comprobante_path'])`, origen `'checkout'`
   - `app/Http/Controllers/EstadoInscripcionesController.php` `registrarCobroAdmin()` (~L388-415): delegar en `confirmarORegistrar()`; eliminar el pluck del staging y el early-return `monto <= 0`
2. **Payloads**:
   - `EstadoInscripcionesController@index`: quitar eager `'comprobantes.imagen'`; setear `pago_visible` por fila (`'A revisar'` si tiene cobros `a_revisar` y `pago !== 'Saldado'`; si no `= pago`) usando la relación `cobros` ya cargada
   - `CobrosController@index` (~L51): agregar `'estado'`; `'comprobantes' => pluck('ruta')` (fix del bug del primer comprobante)
   - `InscripcionesController@index/show` y `GridActividadesController@inscripcion`: eager `'cobros.comprobantes.imagen'` y aplanar a `comprobantes: [{ruta, descripcion}]` (mismo shape que consumen `Inscripcion/Show.vue`, `Inscripciones/Index.vue`, `GridActividades/Inscripcion.vue` — no tocar esos templates)
3. **Comando** `app/Console/Commands/MigrarStagingComprobantes.php` (`cobros:migrar-staging {--dry-run}`), idempotente (ver plan abajo)
4. **Frontend**:
   - `resources/js/Pages/EstadoInscripciones/Index.vue`: columna Pago → `field="pago_visible"` (display + filtro; renombrar clave en `filters` ~L1194); `badgePagoClass` con `'A revisar'` ámbar; `pagoOptions` desde `pago_visible`; panel Comprobante del diálogo de edición lista `cobros[].comprobantes[]` con visor
   - Extraer visor a `resources/js/Components/Dialogs/ComprobanteVisorDialog.vue` (desde `CobroDetalleDialog.vue` L184-207) y reusar
   - `CobroDetalleDialog.vue`: badge estado por cobro, monto ámbar si a revisar, `totalCobrado` solo confirmados + línea "Informado a revisar", corregir `ORIGEN_LABEL` (agregar `checkout`, claves reales `mercadopago`/`importacion`/`pos`; hoy tiene `import`/`webhook` que no existen)
   - `Cobros/Index.vue`: comprobantes múltiples (v-for), columna Estado, total solo confirmados
5. **Limpieza `destroy()`** en EstadoInscripcionesController (~L580): borrar archivos de `cobros.comprobantes.imagen` + soft-delete de cobros
6. **Tests nuevos** en `tests/Feature/Cobros/`: `CobroARevisarTest`, `ConfirmarCobroARevisarTest`, `WebhookConCobroARevisarTest`, `MigrarStagingComprobantesTest` (detalle abajo)
7. **Verificación final**: suite completa (los únicos fallos tolerados: los 4 de ImportarMultievento preexistentes) + `npm run build` + `php artisan cobros:migrar-staging --dry-run` (esperado local: 1 inscripción con staging sin cobro) + corrida real + smoke UI

---

## Decisiones de diseño (aprobadas)

- **Modelo**: NUNCA hay comprobante sin cobro. Subir comprobante ⇒ cobro `a_revisar` con comprobante enlazado. El staging `inscripcion_comprobantes` deja de recibir escrituras (DROP en fase 2).
- **`cobros.estado`** string(20) default `'confirmado'` (no enum). Ortogonal a `origen`; nuevo origen `'checkout'`.
- **Un cobro a revisar NO suma**: `montoCobrado()` filtra por confirmados — cubre saldo/recalculo en los 4 dominios (Inscripcion, InscripcionClase, EstadoCuentaMembresia, Venta).
- **"A revisar" NO entra al enum `inscripciones.pago`**: se deriva como `pago_visible` en el controller (el enum lo consumen reportes/mails/webhook/importadores). Filtro client-side de la datatable sobre ese campo.
- **Invariante**: a lo sumo UN cobro `a_revisar` por cobrable; segunda subida agrega comprobante al existente.
- **Monto provisional** = `max(0, saldoPendiente())`, `fecha_pago = null`; al confirmar se pisa con el real.
- **Confirmación** en `CobroService::confirmarORegistrar()` vía flujo admin existente (marcar Saldado/Parcial). Sin botón nuevo en el diálogo (queda fase 2).
- **Casos borde**: comprobante sobre inscripción saldada → se adjunta al último confirmado; monto ≤ 0 al confirmar (ya pagó MP) → comprobantes pasan al confirmado y el pendiente se soft-deletea; gratuitas nacen Saldadas y no derivan "A revisar".

## Comando `cobros:migrar-staging {--dry-run}` (spec)
Por inscripción con filas en `inscripcion_comprobantes` (imagen_id no nulo): saltear `imagen_id` ya presentes en `cobro_comprobantes` de sus cobros (incluir soft-deleted); con los huérfanos, agregar al cobro `a_revisar` existente o crear uno (`origen 'checkout'`, monto provisional, fecha null) con `registrarComprobanteARevisar`/`agregarComprobantes`. Reportar conteos. Precedente de estilo: `app/Console/Commands/BackfillCobros.php`.

## Tests nuevos (spec)
- `CobroARevisarTest`: subida owner → cobro `a_revisar` origen `checkout` (montoCobrado()==0, pago sigue Pendiente, nada en staging); segunda subida → mismo cobro; subida admin → origen `manual`; subida a saldada → adjunta al confirmado; `finalizarPago` grid con comprobante en sesión → inscripción + cobro a revisar.
- `ConfirmarCobroARevisarTest`: marcar Saldado confirma el MISMO cobro (id estable, monto=saldo, fecha=hoy, comprobantes intactos, sin duplicado); Parcial con `monto_cobrado`; Saldado con saldo 0 → cierra pendiente sin duplicar.
- `WebhookConCobroARevisarTest`: a revisar + pago MP aprobado → cobro MP confirmado, Saldado, el a revisar no suma.
- `MigrarStagingComprobantesTest`: crea/enlaza sin duplicar; re-corrida idempotente.

## Verificación end-to-end
1. `composer test:regression` (tolerados solo los 4 fallos preexistentes de ImportarMultievento).
2. `php artisan cobros:migrar-staging --dry-run` + real (local y luego producción).
3. Smoke UI: checkout con transferencia → badge "A revisar" filtrable → click abre diálogo con comprobante → marcar Saldado → mismo cobro confirmado; diálogo de edición lista comprobantes; vista Cobros muestra estado y todos los comprobantes; "mis inscripciones"/Show/grid siguen mostrando comprobante.

**Producción** (deploy manual, `ssh agendaflex`, PHP `/opt/alt/php82/usr/bin/php`): `composer install` (por el bump de nette/schema), `migrate`, `cobros:migrar-staging --dry-run` → real, en el mismo deploy que el código.

## Fase 2 (fuera de esta tanda)
- DROP `inscripcion_comprobantes` + borrar modelo/relación/referencias (`ImagenesController` ~L146).
- Extender `a_revisar` a membresías (¡ojo al `cobros()->delete()` de `sincronizarMembresia`!).
- Botón "Confirmar cobro" en el diálogo si el uso lo pide.

## Pendiente ajeno a este trabajo (anotado, no bloquea)
- 4 tests de `ImportarMultieventoTest` fallan contra BD de test con datos reales (asserts de conteos globales) — preexistente.
- `composer audit` reporta 46 avisos de seguridad (Laravel y deps desactualizados) — preexistente.

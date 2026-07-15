# Plan: Ledger unificado de cobros (`cobros`)

> Documento de trabajo. Todas las decisiones están cerradas y las Fases 1–7 están **implementadas** en la base dev (ver "Estado de implementación").

## Estado de implementación (2026-07-11)
- **Fases 1–7 implementadas** en dev. Backfill corrido: **688 cobros** (373 actividades con dinero + 314 membresías + 1 venta; las inscripciones gratuitas —montoapagar 0— no generan cobro). Comando `cobros:backfill` idempotente (re-ejecutar no duplica).
- **Tests**: los 10 tests de `tests/Feature/Cobros/` pasan; el suite Feature completo da 94 verdes. Las 4 fallas de `ImportMultieventoTest` son por **datos en la base de tests** (`milarepa_testing` quedó con las 1632 inscripciones + 46 actividades de dev), no por el código — esos tests asumen esas tablas vacías. Fix: dejar `inscripciones`/`actividades` (y dependientes) vacías en la base de tests, conservando solo datos de referencia.
- **Comprobantes**: se enlazan a `cobros.comprobante_id` al crearse el cobro (membresías y confirmación admin; ventas directo). **Almacenamiento unificado en `imagenes`**: `inscripcion_comprobantes` y membresías guardan `imagen_id`/`comprobante_imagen_id` (FK) en vez de paths crudos; los accessors `ruta`/`comprobante` mantienen las vistas Vue sin cambios.
- **UI**: selectores de "medio de pago" agregados en el diálogo admin de inscripciones (`EstadoInscripciones/Index.vue`: edición completa + "marcar saldado", con monto en Parcial) y en el form de clases (`InscripcionClaseForm.vue` + Create/Edit + `metodosPago` desde el controlador). Frontend compila OK.
- **Vista Cobros** (menú Pagos → Cobros): `CobrosController@index` + `resources/js/Pages/Cobros/Index.vue`, DataTable con los cobros de los 4 dominios (fecha, dominio, detalle, monto, medio, referencia, origen, comprobante), buscador global y total. Ruta `cobros.index`.

## Actualización (2026-07-15) — evolución post-implementación
Dos decisiones del plan original se revirtieron tras completar el ledger (commits en `main`):

- **Comprobantes ahora 1:N** (antes 1:1). Nueva tabla hija `cobro_comprobantes` (FK a `imagenes`); `Cobro::comprobantes()` `hasMany`; se dropeó `cobros.comprobante_id` (con backfill). `CobroService::registrar()` acepta `comprobante_ids` (array) + `sincronizarComprobantes()`. `EstadoInscripcionesController::registrarCobroAdmin` enlaza **todos** los comprobantes stageados de la inscripción (antes solo el más reciente). `ImagenesController::usosDeImagen` ahora cuenta las FKs de comprobante (evita borrar una imagen en uso). Test: `CobroMultiComprobanteTest`.
- **Membresías: el cobro es la fuente de verdad** (antes espejo unidireccional cuota→cobro). `pagado` de la cuota se **deriva** de la existencia de su cobro; `fecha_pago`/`info_pago`/`modo` se derivan del cobro (`modo` solo si el método resuelve, para no perder un valor válido). Nuevo `CobroService::recalcularMembresia()`; `recalcularEstadoPago()` ahora maneja membresías (registrar/anular un cobro recomputa la cuota). La cuota conserva `importe` (deuda) y `comprobante_imagen_id` (staging del pago informado y pendiente de aprobación). `MembresiaEspejaCobroTest` quedó invertido.
- **Visor de comprobante unificado en las vistas**: el detalle del pago con sus comprobantes se ve desde `CobroDetalleDialog.vue` (clic en la fecha/badge) en Estado de inscripciones y Estado cuenta membresía; se quitaron las columnas/visores de comprobante redundantes.
- **Suite**: Feature 101/101 verde.

## Contexto

Hoy **no existe una tabla de cobros**. El dinero está desparramado en cuatro dominios, cada uno con su propia representación, y en tres de ellos el «cobro» son columnas desnormalizadas mezcladas con "lo que se debe":

| Dominio | Modelo / tabla | Cómo guarda el pago hoy |
|---|---|---|
| Actividades | `Inscripcion` / `inscripciones` | columnas `montoapagar` + desglose, `pago` enum, `fecha_pago`, `referencia_pago`. Medio de pago **no se persiste** (solo sesión). MercadoPago muta la fila vía webhook. |
| Clases | `InscripcionClase` / `inscripciones_clases` | columnas `montoApagar` + desglose, `pago` enum. Sin medio, sin fecha, sin comprobante. Pago manual. |
| Membresías | `EstadoCuentaMembresia` / `estado_cuenta_membresias` | **ya es un ledger mensual**: `importe`, `pagado` bool, `estado`, `modo` (medio), `fecha_pago`, `comprobante`. |
| Ventas de libros | `Venta` / `ventas` | fila POS cerrada: `montoTotal`, `modo`, `fecha`, `comprobante_id`. |

El objetivo es introducir un **ledger de cobros único y polimórfico** (`cobros`) que registre **cobros efectivamente realizados** (1:N) para los cuatro dominios, sin perder la información de "lo que se debe" (que sigue en cada entidad). Habilita: cobros parciales/cuotas reales, capturar por fin el **medio de pago** en actividades/clases, unificar comprobantes, y tener una fuente única para reportes de caja.

## Decisiones resueltas
- **Nombre**: `Cobro` / tabla `cobros`.
- **Cardinalidad**: 1:N — una entidad puede tener varios cobros.
- **Qué migra**: *solo el cobro realizado*. El desglose de precios / monto adeudado queda en cada entidad.
- **Alcance**: los 4 dominios, vía relación polimórfica.
- **Membresías**: **espejo UNIDIRECCIONAL** (`estado_cuenta_membresias → cobros`). La cuota es la fuente de verdad; marcar pagada crea/sincroniza el cobro; crear/editar un cobro NO modifica la cuota. **⟶ Actualizado (2026-07-15): se invirtió — el cobro es ahora la fuente de verdad y `pagado`/`fecha_pago`/`modo`/`info_pago` de la cuota se derivan del cobro. Ver "Actualización".**
- **Histórico**: **backfill completo** vía comando idempotente.
- **Medio de pago**: FK `metodo_pago_id` → `metodos_pago` (catálogo existente) + `referencia` (texto libre para detalle: códigos de comprobante de transferencia, `payment_id` de MP, etc.).
- **Enum `pago`**: se mantiene como **caché derivada** (recalculada al cambiar cobros), no se deprecia.
- **`Parcial` en backfill**: **no aplica** — hay 0 parciales en la base (ver hallazgos). Regla defensiva: loguear para revisión, nunca fabricar importe. Hacia adelante, el flujo nuevo captura el monto cobrado.
- **Comprobantes**: **unificar ahora** en `cobros.comprobante_id` (FK a `imagenes`), **uno por cobro**. **⟶ Actualizado (2026-07-15): ahora 1:N vía la tabla `cobro_comprobantes` (se dropeó `cobros.comprobante_id`). Ver "Actualización".**
- **`cobrable_type`**: **morph map** con alias cortos y estables.
- **Moneda**: `moneda_id` nullable (FK a `monedas`, `null`=pesos) como seguro a futuro; hoy todo es pesos.

## Hallazgos empíricos (base real `milarepa`, consulta read-only)
- `inscripciones`: **1632** → 1305 `Saldado`, 327 `Pendiente`, **0 `Parcial`**.
- `inscripciones_clases`: **0** filas (módulo aún sin uso).
- `ventas`: **1** (`Transferencia`).
- `estado_cuenta_membresias`: **422** total, **314** pagadas. `modo`: Suscripción 76, (vacío) 209, Transferencia 65, Otro 67, Efectivo 5.
- `metodos_pago` ya existentes: Efectivo, Transferencia, MercadoPago Alias, Tarjeta de crédito, Tarjeta de débito, Getnet, Mercadopago QR, Mercadopago Link, Mercado Pago. **Faltan solo "Suscripción" y "Otro"** (usados por membresías).
- **Sizing del backfill**: 1305 (act. Saldadas) + 314 (membresías pagadas) + 1 (venta) ≈ **1620 cobros**. Clases y parciales: 0.

---

## Diseño del modelo `Cobro` / tabla `cobros`

Migración nueva `database/migrations/AAAA_MM_DD_000000_create_cobros_table.php` (estilo `2026_03_21_030000_create_ventas_table.php` + sembrado de permisos como en `2026_05_28_000001_create_precio_grupos_table_and_permissions.php`).

Columnas:
- `id`
- `morphs('cobrable')` → `cobrable_type` (string) + `cobrable_id` (unsignedBigInteger) + índice compuesto. **No lleva FK real** (limitación del polimorfismo; integridad a nivel app).
- `monto` `decimal(10,2)` — importe **efectivamente cobrado** en este evento.
- `moneda_id` `foreignId` nullable → `constrained('monedas')->nullOnDelete()`. `null` = pesos (implícito). Seguro para futuro multi-moneda; sin lógica adicional por ahora.
- `fecha_pago` `date` nullable (si falta en backfill se usa `created_at`).
- `metodo_pago_id` `foreignId` nullable → `constrained('metodos_pago')->nullOnDelete()` (mismo patrón que `botones_pago`). Medio de pago estructurado.
- `referencia` `string` nullable — detalle de texto libre (código de comprobante de transferencia, `payment_id` de MP, columna `Forma` de planillas).
- `comprobante_id` `foreignId` nullable → `constrained('imagenes')->nullOnDelete()`. **Comprobante unificado, uno por cobro.**
- `observaciones` `text` nullable.
- `registrado_por` `foreignId` nullable → `constrained('users')->nullOnDelete()`.
- `origen` `string(20)` nullable — `manual` | `mercadopago` | `importacion` | `generador` | `backfill`. Trazabilidad + idempotencia del backfill.
- `timestamps()` + `softDeletes()`.
- Índices: `index('fecha_pago')`, `index('origen')`.

`down()`: `Schema::dropIfExists('cobros')`.

Modelo `app/Models/Cobro.php`:
```php
class Cobro extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'cobros';

    protected $fillable = [
        'cobrable_type','cobrable_id','monto','moneda_id','fecha_pago','metodo_pago_id',
        'referencia','comprobante_id','observaciones','registrado_por','origen',
    ];
    protected $casts = ['monto' => 'decimal:2', 'fecha_pago' => 'date'];

    public function cobrable()   { return $this->morphTo(); }
    public function metodoPago() { return $this->belongsTo(MetodoPago::class, 'metodo_pago_id'); }
    public function moneda()     { return $this->belongsTo(Moneda::class, 'moneda_id'); }
    public function comprobante(){ return $this->belongsTo(Imagen::class, 'comprobante_id'); }
    public function registrador(){ return $this->belongsTo(User::class, 'registrado_por'); }
}
```

### Morph map (alias cortos)
En `app/Providers/AppServiceProvider::boot()`:
```php
Relation::enforceMorphMap([
    'inscripcion'       => \App\Models\Inscripcion::class,
    'inscripcion_clase' => \App\Models\InscripcionClase::class,
    'membresia_cuota'   => \App\Models\EstadoCuentaMembresia::class,
    'venta'             => \App\Models\Venta::class,
]);
```
Así `cobrable_type` guarda `'inscripcion'` en vez de `App\Models\Inscripcion` — legible y a prueba de renombres.

### Cambios en `metodos_pago`
- **Agregar `use SoftDeletes;`** a `app/Models/MetodoPago.php` (la tabla ya tiene `deleted_at` pero el modelo borra físico; con la FK nueva conviene baja lógica).
- **Seed idempotente** de los métodos faltantes (`firstOrCreate` por `nombre`): **"Suscripción"** y **"Otro"** (el resto ya existen). Sembrar en la migración de `cobros` o en un `MetodoPagoSeeder`.

---

## Modelos y relaciones (trait `TieneCobros`)

Crear `app/Models/Concerns/TieneCobros.php`:
```php
trait TieneCobros
{
    public function cobros()        { return $this->morphMany(Cobro::class, 'cobrable'); }
    public function montoCobrado()  { return (float) $this->cobros()->sum('monto'); }
    public function saldoPendiente(){ return round($this->totalAdeudado() - $this->montoCobrado(), 2); }
    abstract public function totalAdeudado(): float; // cada modelo mapea su columna de total
}
```
`use TieneCobros;` + `totalAdeudado()` en: `Inscripcion`→`montoapagar`, `InscripcionClase`→`montoApagar`, `EstadoCuentaMembresia`→`importe`, `Venta`→`montoTotal`.

Confirmado por código: **`montoapagar`/`montoApagar` es el TOTAL, nunca se reduce** al cobrar. Por eso `totalAdeudado() = montoapagar` es correcto y `saldoPendiente = total − Σcobros`.

Actividades y clases suman `recalcularEstadoPago()` (ver servicio) que actualiza el enum `pago` (caché) y, en actividades, `estado`. `Venta` y `EstadoCuentaMembresia` no derivan estado.

---

## Servicio `CobroService`

`app/Services/CobroService.php`, único punto de entrada:
- `registrar(Model $cobrable, array $datos): Cobro` — crea el `Cobro`; si el cobrable deriva estado, llama `recalcularEstadoPago()`.
- `recalcularEstadoPago(Model $cobrable): void` — inscripciones/clases: `Σcobros` vs `totalAdeudado()` → `Saldado` / `Parcial` / `Pendiente` (+ `estado` en actividades). Reemplaza la lógica duplicada `resolverEstadoSegunMonto()`.
- `sincronizarMembresia(EstadoCuentaMembresia $cuota): void` — espejo unidireccional: si `pagado==true` hace `updateOrCreate` del cobro (`monto=importe`, `metodo_pago_id`=resolver(`modo`), `fecha_pago`, comprobante); si `pagado==false`, soft-delete del espejo.
- `resolverMetodoPago(?string $modo): ?int` — mapea strings de medio (`ventas.modo`, `estado_cuenta_membresias.modo`) a `metodos_pago.id` por nombre normalizado (Efectivo→1, Transferencia→2, Suscripción/Otro→seed); vacío/desconocido → `null`.

---

## Integración por dominio (wiring)

**Ventas** (arrancar por acá, más simple): `VentasLibrosController::store` — tras crear la `Venta`, `CobroService::registrar($venta, [...])` con `monto=montoTotal`, `metodo_pago_id`=resolver(`modo`), `fecha_pago=fecha`, `comprobante_id`, `registrado_por=vendedor_id`, `origen='manual'`.

**Membresías** (espejo unidireccional): puntos donde una cuota pasa a `pagado=true` (o cambian `importe`/`modo`/`fecha_pago` estando pagada): `EstadoCuentaMembresiasController` (`store`, `update`, `uploadComprobante`), `GeneradorEstadosCuentaMembresiaService`, `RenovarMembresiasMensual`, `ImportarMembresiasService`. Tras persistir la cuota → `CobroService::sincronizarMembresia($cuota)`. Dirección: `estado_cuenta_membresias → cobros`, nunca al revés.

**Actividades**:
- `MercadoPagoWebhookController::handle` — además de setear `pago/fecha_pago/referencia_pago`, `CobroService::registrar($inscripcion, [...])` con `metodo_pago_id`=fila MercadoPago, `referencia=payment->id`, `origen='mercadopago'`.
- `EstadoInscripcionesController::update` y `marcarPago` — al marcar `Saldado`/`Parcial`, registrar el `Cobro`. **Acá se captura el medio de pago** (nuevo selector de `metodo_pago_id` en el diálogo admin) y, para `Parcial`, **el monto cobrado ingresado** (esto cierra el agujero actual: hoy no se guarda cuánto se cobró en un parcial).
- `InscripcionesController::store` y `GridActividadesController::finalizarPago` — registrar cobro cuando el cobro es inmediato; si queda `Pendiente`, no se crea cobro.
- **Importadores** (`ImportarInscripcionesService::crearInscripcion()`, `ImportarMultieventoService::crearInscripcion()` y `::actualizarInscripcion()`) — emitir `Cobro` (`origen='importacion'`) **solo con evidencia de pago real** (`Valor>0` o `FechaPago` o `Forma`); gratuitas/`Saldado` sin datos NO generan cobro. `metodo_pago_id=null`, `Forma`→`referencia`, `fecha_pago=FechaPago`. Idempotente: `updateOrCreate` por `(cobrable, origen='importacion')`.

**Clases**: `InscripcionesClasesController::persistirInscripcionClase` — al marcar `Saldado`/`Parcial`, `CobroService::registrar(...)`. Agregar selector de `metodo_pago_id` al form (`InscripcionClaseForm.vue`). Tharpa sigue en `HistoricoPedidoLibro` (inventario); no se duplica cobro.

> Ignorar los `*- copia.php` en `app/Http/Controllers/` (copias muertas).

---

## Comprobantes unificados

Hoy hay 3 mecanismos: `inscripcion_comprobantes` (tabla propia, path string, 1:N), `estado_cuenta_membresias.comprobante` (string inline, 1:1), `ventas.comprobante_id` (FK a `imagenes`, 1:1). Se unifican en **`cobros.comprobante_id` → `imagenes`, uno por cobro** (cada evento de cobro tiene su comprobante; varios comprobantes = varios cobros).

- **Altas nuevas**: los endpoints de subida (`GridActividadesController`/`InscripcionesController::uploadComprobante`, `EstadoCuentaMembresiasController::uploadComprobante`, `VentasLibrosController`) crean un `Imagen` (reutilizar trait `app/Concerns/ProcesaImagenAlGuardar.php` + `OptimizadorImagenService`) y setean `cobros.comprobante_id`.
- **Migración de históricos**: convertir los path string en filas `Imagen` (`nombre`=basename, `ruta`=path actual) y enlazarlos al cobro correspondiente. `ventas` ya usa `imagenes` (enlace directo). Para inscripciones con varios comprobantes (raro; la UI hoy muestra solo el primero) se enlaza el más reciente al cobro y los extras se loguean.
- `inscripcion_comprobantes` y la columna `estado_cuenta_membresias.comprobante` quedan como **legacy de solo lectura** tras migrar (no se escriben más); no se borran en esta fase.

---

## Backfill completo (comando idempotente)

`app/Console/Commands/BackfillCobros.php` (`php artisan cobros:backfill [--dominio=] [--dry-run]`). Idempotente: no crea si ya existe un `Cobro` con `origen='backfill'` para ese cobrable. Chunked. Reglas (con conteos reales):
- **Ventas** (1): cada `Venta` → 1 cobro completo (`monto=montoTotal`, `metodo_pago_id`=resolver(`modo`), `fecha`, `comprobante_id`, `registrado_por`).
- **Membresías** (314 pagadas): cada cuota `pagado=true` → 1 cobro (`monto=importe`, `metodo_pago_id`=resolver(`modo`), `fecha=fecha_pago`, comprobante migrado). `modo` vacío → `metodo_pago_id=null`.
- **Actividades** (1305 Saldadas): `pago='Saldado'` → 1 cobro por `montoapagar`, `fecha=fecha_pago ?? updated_at`, `referencia=referencia_pago`, `metodo_pago_id=null` (el medio de sesión no se persistía). `Pendiente` (327) → nada.
- **Parcial** (0 hoy): regla defensiva — no crear cobro, loguear a reporte de reconciliación. No se fabrica importe.
- **Clases** (0 hoy): sin datos.
- Resumen final: creados / omitidos / a revisar.

---

## Permisos (Spatie)
En la migración de `cobros`, sembrar `create/read/update/delete cobros` (guard `web`) y asignar a `admin`, patrón de `2026_05_28_000001_create_precio_grupos_table_and_permissions.php`.

---

## Fases de implementación
1. **Núcleo** (sin cambio de comportamiento): migración `cobros` (con `metodo_pago_id` FK + permisos), morph map en `AppServiceProvider`, `MetodoPago` con `SoftDeletes` + seed de "Suscripción"/"Otro", modelo `Cobro`, trait `TieneCobros` + `totalAdeudado()` en los 4 modelos, `CobroService`, tests del núcleo.
2. **Ventas**: wiring `store` + backfill (1) + tests.
3. **Membresías (espejo)**: `sincronizarMembresia` en los 5 puntos + backfill (314) + tests (marcar pagada crea cobro; despagar da de baja).
4. **Actividades**: webhook MP + flujos admin (captura de `metodo_pago_id` y monto en `Parcial`) + `GridActividadesController` + importadores (legacy y multievento) + backfill (1305 Saldadas) + tests.
5. **Clases**: `persistirInscripcionClase` + selector de medio en el form + tests (backfill 0).
6. **Comprobantes unificados**: reencaminar endpoints de subida a `cobros.comprobante_id` + migración de históricos (path string → `Imagen`) + tests.
7. **Backfill run + verificación end-to-end + reporte de reconciliación**.

Cada fase es entregable y el enum `pago` sigue funcionando como caché.

---

## Archivos clave

**Nuevos:**
- `database/migrations/AAAA_MM_DD_000000_create_cobros_table.php`
- `app/Models/Cobro.php`, `app/Models/Concerns/TieneCobros.php`
- `app/Services/CobroService.php`
- `app/Console/Commands/BackfillCobros.php`
- (posible) `database/seeders/MetodoPagoSeeder.php`
- Tests en `tests/Feature/Cobros/`

**Modificados:**
- `app/Providers/AppServiceProvider.php` (morph map)
- `app/Models/MetodoPago.php` (SoftDeletes), `app/Models/Inscripcion.php`, `app/Models/InscripcionClase.php`, `app/Models/EstadoCuentaMembresia.php`, `app/Models/Venta.php` (trait + `totalAdeudado`)
- `app/Http/Controllers/VentasLibrosController.php`
- `app/Http/Controllers/EstadoCuentaMembresiasController.php`, `app/Services/GeneradorEstadosCuentaMembresiaService.php`, `app/Console/Commands/RenovarMembresiasMensual.php`, `app/Services/ImportarMembresiasService.php`
- `app/Http/Controllers/MercadoPagoWebhookController.php`, `EstadoInscripcionesController.php`, `InscripcionesController.php`, `GridActividadesController.php`
- `app/Services/ImportarInscripcionesService.php`, `app/Services/ImportarMultieventoService.php`
- `app/Http/Controllers/InscripcionesClasesController.php` + `resources/js/Components/Formularios/InscripcionClaseForm.vue` + `resources/js/Pages/EstadoInscripciones/Index.vue` (selector de medio + monto en Parcial)
- Endpoints/vistas de comprobante (subida → `cobros.comprobante_id`)

---

## Tests (Feature + `DatabaseTransactions`, sin factories, `Model::create`) en `tests/Feature/Cobros/`
- `CobroPolimorficoTest` — `cobros()` morphMany + `montoCobrado()`/`saldoPendiente()` para los 4 cobrables; alias de morph map correctos.
- `RecalculoEstadoInscripcionTest` — cobros parciales/total actualizan `pago`/`estado`; `saldoPendiente` correcto.
- `VentaGeneraCobroTest` — `store` crea 1 cobro = `montoTotal` con `metodo_pago_id` resuelto.
- `MembresiaEspejaCobroTest` — marcar pagada crea el cobro espejo; despagar lo da de baja; crear un cobro NO toca la cuota (unidireccional).
- `ImportGeneraCobroTest` — import con evidencia crea cobro (`origen='importacion'`, `Forma`→`referencia`, `metodo_pago_id=null`); gratuita/`Saldado` sin datos no crea; re-import multievento no duplica.
- `ComprobanteUnificadoTest` — subir comprobante en cada dominio crea `Imagen` y setea `cobros.comprobante_id`.
- `BackfillCobrosTest` — idempotente; Saldado→cobro, Pendiente→nada, Parcial→log; membresías pagadas→cobro; venta→cobro.

---

## Verificación end-to-end
1. `php artisan migrate` sobre `milarepa_testing` + revisión de `down()`. Confirmar `php -v` ≥ 8.3.
2. `php artisan test --filter=Cobros` en verde + suite completo sin regresiones nuevas.
3. Manual (tinker/UI): venta→1 cobro; cuota de membresía pagada→cobro espejo (y despagar→baja); inscripción vía webhook MP o admin `Saldado`→cobro con `metodo_pago_id`/`referencia`; admin `Parcial` con monto→`Parcial` y `saldoPendiente` correcto; subir comprobante→`cobros.comprobante_id`.
4. `php artisan cobros:backfill --dry-run` → esperar ~**1620** cobros (1305 act. + 314 memb. + 1 venta), 0 parciales/clases; revisar reporte antes de la corrida real.

---

## Fuera de alcance (vigente)
- **Rediseñar el checkout público** para crear el cobro al iniciar el pago (hoy el comprobante se sube y persiste antes de que exista el cobro).
- **Borrar `inscripcion_comprobantes`** como tabla: se mantiene como *staging* pre-cobro (con `imagen_id`); depende del rediseño del checkout.
- **Pago online (MercadoPago)** para clases/membresías (hoy MP solo en actividades).
- (Opcional a futuro) **Editar/anular cobros desde el diálogo del pago**: la inversión de membresías ya dejó el terreno listo (`recalcularEstadoPago`/`recalcularMembresia` recomputan la cuota al crear/borrar un cobro).

> **Ya implementados** (antes fuera de alcance): multi-comprobante por cobro (1:N) y migrar membresías a `cobros` como fuente de verdad — ver **"Actualización (2026-07-15)"**. Los `*- copia.php` y los paths crudos legacy también se eliminaron/unificaron en `imagenes`.

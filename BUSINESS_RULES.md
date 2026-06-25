# Reglas de negocio — Milarepa

Reglas operativas extraídas del código (no de documentación previa). Cada regla cita el archivo y línea donde reside la lógica. Complementa [ARCHITECTURE.md](ARCHITECTURE.md) (estructura técnica) y [README.md](README.md).

> Convención: cuando una regla cita "líneas aproximadas", significa que la lógica puede haberse movido por refactors menores. Validar contra el código antes de implementar cambios dependientes.

---

## 1. Dominio

Centro de meditación budista de la tradición **Kadampa**. Vocabulario propio:

- **Tharpa**: literatura budista sagrada. Determina el inventario por entidad.
- **Entidad**: sede/centro físico. Eje organizativo de inventario, ventas, préstamos y clases.
- **Tarjetas Kadampa**: nombre comercial de las membresías.
- **Ciclo**: agrupador temporal de clases recurrentes.
- **Oración cantada**: práctica espiritual con calendario propio (periodicidad + sobrescritura mensual).

---

## 2. Inscripciones a actividades (cursos/retiros)

### 2.1 Cálculo del monto a pagar

`GridActividadesController` línea ~500:

```
montoTotal = montoActividad
           + montoGrabacion (si aplica)
           + suma(precio de comidas seleccionadas)
           + suma(precio de transportes seleccionados)
           + suma(precio de hospedajes seleccionados)
           + monto_invitados            # Σ montoapagar de cada invitado (ver 2.6)
```

- Si `montoTotal <= 0` → estado pago = `"Saldado"`.
- Si `montoTotal > 0` → estado pago = `"Pendiente"`.
- El total (incluidos los invitados) se cobra siempre a la **persona principal**: una sola
  inscripción, un solo comprobante / medio de pago para todo el grupo.

### 2.2 Resolución del precio según membresía y fecha

`GridActividadesController` líneas ~1116 y ~1145:

1. Si hoy `<=` `actividad.pagoAnticipado` → usa `EsquemaDescuento`. Si no → usa `EsquemaPrecio`.
2. Resolución de línea del esquema (en orden de preferencia):
   - membresía exacta + moneda exacta
   - moneda exacta sin membresía
   - membresía exacta cualquier moneda
   - sin membresía cualquier moneda
   - primera línea disponible (fallback)

### 2.3 Estados posibles de una inscripción

| Campo | Valores |
|---|---|
| `inscripcion` | `Registrada`, `Confirmada` (auto si monto ≤ 0) |
| `pago` | `Saldado`, `Parcial`, `Pendiente` |
| `asistencia` | `presente`, `ausente`, `Pendiente` |
| `envioLinkStream` | `enviado`, `pendiente`, `No aplica` |
| `envioGrabacion` | `Enviada`, `Pendiente`, `No aplica` |

### 2.4 Usuario registrado vs. invitado (GuestUser)

`GridActividadesController` líneas ~915-1033:

- Si el inscripto **es invitado** y `guest.registrar_datos = true` y el email **no existe** en `users` → crea `GuestUser` con esos datos.
- Si el inscripto **es usuario registrado** → valida unicidad `(user_id, actividad_id)` (no permite inscripción doble).
- Tanto `Inscripcion.user_id` como `Inscripcion.guest_user_id` pueden estar presentes; uno excluye al otro semánticamente.

### 2.5 QR de asistencia

`AsistenciasController` líneas ~31-90:

- Generado con `URL::signedRoute` (URL firmada de Laravel).
- Sin expiración explícita — la firma queda válida indefinidamente salvo cambio de `APP_KEY`.
- Al escanear: valida firma con `URL::hasValidSignature()`, crea/actualiza `Asistencia` y marca `inscripcion.asistencia = 'presente'`.

### 2.6 Invitados / acompañantes

> **No confundir con `GuestUser`** (ver 2.4). Aquí "invitado" = acompañante/familiar que la persona
> principal suma a **su** inscripción. No es un usuario del sistema ni tiene cuenta ni membresía.

En la pantalla de pago, antes de terminar la inscripción, el asistente puede agregar **hasta 10
invitados** con datos mínimos (nombre, apellido, teléfono opcional para seguridad/emergencia).
Modelo `App\Models\Invitado` (tabla `invitados`), hijo de `Inscripcion` (una inscripción → N invitados).

Reglas:

- **Precio sin descuento**: cada invitado paga siempre el **precio general** de la actividad,
  aunque la persona principal tenga membresía con descuento.
- **Servicios por invitado**: cada invitado puede elegir sus propios grabación / comidas / transporte /
  hospedaje (pivotes `invitado_comida`, `invitado_transporte`, `invitado_hospedaje`). Cada ítem suma
  al `montoapagar` del invitado.
  ```
  invitado.montoapagar = precioGeneral
                       + montoGrabacion (si incluye_grabacion)
                       + suma(comidas) + suma(transportes) + suma(hospedajes)
  ```
- **Modalidad por invitado**: un invitado puede cursar **online sólo si** la actividad es
  *"Presencial y Online Abierta"*. En cualquier otro caso (cerrada / presencial / online puro) se
  fuerza `online = false` en el backend, aunque el payload lo mande en `true`.
- **Asistencia individual**: cada invitado tiene su propio `asistencia` (`Presente`/`Ausente`/`Pendiente`),
  gestionada por el admin desde "Estado de inscripciones" vía `PATCH /invitados/{invitado}/asistencia`
  (`InvitadosController::asistencia`, sólo roles admin/editor).
- **Una sola inscripción**: los invitados cuelgan de la inscripción del principal; el chequeo de
  duplicados `(user_id, actividad_id)` no cambia. La suma de sus montos se guarda en
  `inscripciones.monto_invitados` y se incluye en `montoapagar` (ver 2.1).
- **Persistencia transaccional**: la creación/actualización de la inscripción y sus invitados (con
  `sync` de pivotes) ocurre dentro de `DB::transaction`. En el camino de actualización (pago de una
  inscripción existente) los invitados se **borran y recrean**.
- **Fuera de alcance v1**: edición/eliminación de invitados post-inscripción por el asistente. En
  actividades **puramente online** se oculta "Agregar invitado" (el flujo apunta a eventos con
  componente presencial). *(El cupo de hospedaje sí se controla — ver 2.7.)*

Backend en `GridActividadesController::finalizarPago()` (validación `invitados[] max:10`); el cálculo
y la persistencia viven en `App\Services\InscripcionServiciosService` (`prepararInvitados()` /
`persistirInvitados()` / `montosServicios()`), compartido con la edición admin (ver 2.8). Frontend en
`Pago.vue` + subcomponente `Components/Actividades/ServiciosActividadSelector.vue` (reutilizado por
principal e invitados). Los invitados se muestran en: email de confirmación/registro, "Mis
inscripciones", pantalla de inscripción registrada y el panel admin "Estado de inscripciones".

### 2.7 Cupo de acomodaciones de hospedaje

> Modelo: **`LugarHospedaje`** (lugar físico) → **`Hospedaje`** = *acomodación* (tipo de habitación,
> con nombre y precio). El **cupo NO vive en la acomodación**, sino en la relación **actividad ↔
> acomodación** (pivote `actividad_hospedaje.cantidad`), porque la misma acomodación puede ofrecerse
> con distinto cupo en cada actividad/fecha.

- **Dónde se carga el cupo**: en el **formulario de la Actividad** (`ActividadForm.vue`), al activar
  "Ofrece hospedaje" y seleccionar acomodaciones, aparece el bloque **"Cupo por acomodación"** con un
  número por acomodación. `cantidad` **vacío/null = ilimitado** (sin control). Se persiste con
  `Actividad::hospedajes()->sync([id => ['cantidad' => N]])` (`withPivot('cantidad')`).
- **Disponibilidad por conteo** (sin contador mutable): se calcula en vivo como
  `disponibles = actividad_hospedaje.cantidad − reservas_activas`, donde `reservas_activas` =
  inscripciones del titular con ese `hospedaje_id` **+** filas de `invitado_hospedaje` de invitados de
  esa actividad con ese hospedaje. La "reserva" es implícita: existe mientras existe la inscripción/invitado.
- **Reserva al confirmar**: no hay "hold" temporal mientras se navega. Se valida disponibilidad al
  **confirmar** (`finalizarPago`) y al **editar** (admin) **dentro de la transacción**, con
  `lockForUpdate()` sobre las filas del pivote para evitar sobreventa concurrente. Si se supera el cupo
  se rechaza con **422** nombrando la acomodación ("La acomodación 'X' ya no tiene cupo disponible.").
  La validación cuenta **titular + todos sus invitados** juntos.
- **Liberación automática**: al **borrar** la inscripción (hard delete → cascade borra invitados e
  `invitado_hospedaje`) o cuando el **admin edita y quita** la acomodación, la unidad se libera sola
  (el conteo baja). No hay código de liberación ni job por tiempo.
- **UI**: en la selección, cada acomodación muestra **"quedan N"** o **"Agotado"** (deshabilitada) según
  `actividad.hospedajes[].disponibles`. La disponibilidad mostrada **excluye la propia inscripción**
  (para poder mantener/re-elegir lo ya reservado al editar). El backend es la fuente de verdad y
  rechaza la sobreventa al guardar.

Lógica en `App\Services\HospedajeCupoService` (`disponibles()`, `requeridos()`, `validar()`); se invoca
desde `GridActividadesController::finalizarPago/pago` y `EstadoInscripcionesController::update/editarData`.

### 2.8 Edición admin de inscripciones (recálculo)

Desde "Estado de inscripciones", el admin/editor edita una inscripción en un **dialog completo**
(`EstadoInscripcionesController::update`, `PUT /estadoinscripciones/{id}`):

- Permite cambiar **estado de pago**, **modalidad online**, y **agregar/quitar servicios** del titular
  y de los **invitados** (con su propio selector de servicios).
- El **monto NO se escribe a mano**: se **recalcula** siempre desde los servicios elegidos (titular a su
  precio de membresía ya guardado; invitados a precio general), reusando `InscripcionServiciosService`.
  Respeta el cupo de hospedaje (2.7).
- Datos del dialog vía `GET /estadoinscripciones/{id}/editar-data`. El atajo **"marcar saldado"** usa
  `PATCH /estadoinscripciones/{id}/pago` (`marcarPago`), que sólo cambia el estado de pago y **no toca**
  servicios ni invitados.

### 2.9 Crear inscripción iniciada por el admin (recepción)

El admin/editor puede inscribir a una persona **en su nombre** (caso típico: gente mayor que no usa el
sistema, atendida en recepción), incluyendo a sus **invitados**. Botón **"Crear inscripción"** en
"Estado de inscripciones".

- El dialog pide sólo **(1) la actividad** y **(2) el participante**, con un radio:
  - **Participante existente** → buscador autocomplete (`GET /estadoinscripciones/usuarios/buscar`,
    `buscarUsuarios`, filtra por nombre/email; el `user_id` viaja directo porque el endpoint es sólo admin).
  - **Participante nuevo** → reusa `GuestUserForm` → crea un `GuestUser`, con el toggle *registrar datos*
    para crearle opcionalmente también un `User` (igual que el flujo público).
- El select de actividades muestra **sólo actividades activas y no finalizadas** (`estado = true` y
  `fecha_fin >= now()`).
- Al confirmar, `EstadoInscripcionesController::crearInscripcionPrepare`
  (`POST /estadoinscripciones/crear/prepare`) **escribe la sesión `grid_pago`** con el participante
  elegido y redirige a la **pantalla de pago existente** (`Pago.vue`), donde el admin completa servicios,
  **invitados**, hospedaje (con cupo, ver 2.7), comprobante y método de pago.
- **Importante:** `pago()` y `finalizarPago()` resuelven participante, membresía y precio desde la sesión
  `grid_pago` (no desde `auth()`), por lo que la inscripción y el precio quedan a nombre del
  **participante destino**, aunque sea el admin quien la opera. Las reglas de validación del guest se
  comparten con el flujo público vía `GridActividadesController::reglasGuest()`.

---

## 3. Inscripciones a clases

`InscripcionesClasesController` líneas ~211, ~452-480, ~226-228.

### 3.1 Tres montos desglosados

```
montoActividad = esquemaPrecio (según membresía elegida)
montoTharpa    = suma(precio libros tharpa seleccionados)
montoTienda    = ingresado manualmente
montoApagar    = montoActividad + montoTharpa + montoTienda
```

### 3.2 Validación de stock de tharpa

Líneas ~172-192, ~281-322:

- Cada artículo `tharpa` requiere stock > 0 en `InventarioEntidadLibro` para la entidad de la clase.
- Al persistir la inscripción:
  - Se descuenta del inventario con `lockForUpdate()` (evita race conditions).
  - Se registra cada movimiento en `HistoricoPedidoLibro` con `cantidad_inicial/final/vendida`.
- Al **editar** una inscripción y quitar libros, se suma de vuelta al inventario (líneas ~324-347).

---

## 4. Membresías

### 4.1 Estados

`EstadoCuentaMembresia.php`:

- `ESTADO_ACTIVA = 'Activa'`
- `ESTADO_EXPIRADA = 'Expirada'`

> No existen los estados `VENCIDA` ni `INACTIVA` en el código actual, pese a que la UI puede usar términos diferentes. Si aparecen, es etiqueta visual sobre los mismos estados base.

### 4.2 Modos de pago

`EstadoCuentaMembresia.php` constantes: `Efectivo`, `Transferencia`, `Suscripción`, `Tarjeta Crédito`, `Tarjeta Débito`, `Otro`.

**Regla especial**: si `modo = 'Suscripción'`, el estado de cuenta se marca **automáticamente como pagado** al crearse.

### 4.3 Renovación mensual

Comando `RenovarMembresiasMensual` (líneas ~70-126):

Para cada usuario con `membresiaUsuario.membresia_id`:

1. Si **ya existe** `EstadoCuentaMembresia` con `mes_pagado = mes actual`:
   - Mantiene estado = `ACTIVA`.
   - Si modo = `Suscripción` → marca `pagado = true`.
2. Si **no existe** para el mes actual:
   - **Expira** todos los registros previos del usuario (estado → `EXPIRADA`).
   - **Crea** nuevo registro para el mes actual con estado = `ACTIVA`.
   - Si modo = `Suscripción` → marca `pagado = true` automáticamente.
3. **Sólo una membresía activa por usuario** a la vez. Si hay duplicados, todas las anteriores quedan `EXPIRADA`.

### 4.4 Modalidad online vs. presencial

`GridActividadesController` líneas ~1184-1197:

- `User.membresia_online (bool)` define si el usuario tiene modalidad online habilitada.
- Si `Actividad.modalidad = "Presencial y online"`:
  - Si `user.membresia_online = false` → la UI muestra sólo opción presencial.
  - Si `user.membresia_online = true` → permite elegir.
- `EstadoCuentaMembresia.modalidad` se actualiza al editar (línea ~75, ~94).

### 4.5 Asignación / eliminación manual

`MembresiasGestionController`:

- **`asignar`**: si se asigna nueva membresía, expira todas las activas anteriores del usuario y crea `EstadoCuentaMembresia` para el mes actual.
- **`eliminar`**: pone `EstadoCuentaMembresia` activos del usuario a `EXPIRADA` y limpia `membresia_id` del usuario.

---

## 5. Inventario tharpa y movimientos

### 5.1 Modelo de inventario

- `InventarioEntidadLibro` mantiene `cantidad` por par `(entidad_id, libro_id)`.
- **No hay reservas**: el stock es real-time, se descuenta en el momento de la operación.

### 5.2 Operaciones que afectan el stock

| Operación | Efecto |
|---|---|
| Venta directa | Descuenta `cantidad` (registra `Venta`) |
| Inscripción a clase con artículos tharpa | Descuenta `cantidad` (registra `HistoricoPedidoLibro`) |
| Préstamo entre entidades | `PrestamoAnexo` — descuenta de `prestadora`, suma en `receptora` |
| Devolución | `DevolucionAnexo` — descuenta de quien devuelve, suma en quien recibe |
| Edición de inscripción retirando un libro | Suma de vuelta al inventario |

### 5.3 Modelo Venta

- Campos: fecha, entidad, libro, cantidad, precio_unitario, montoTotal, modo (cash/transferencia/cheque), comprobante (imagen), vendedor.
- Validación: requiere `cantidad <= inventario.cantidad`.

### 5.4 Préstamo / devolución

- **`PrestamoAnexo`**: prestadora_id, receptora_id, libro_id, cantidad, usuario_responsable, fecha.
- **`DevolucionAnexo`**: devolvedor_id, prestador_id (referencia inversa), libro_id, cantidad.
- **No hay estado intermedio** en el modelo (no existe "préstamo en curso vs. cerrado") — la trazabilidad se infiere por ausencia/presencia de `DevolucionAnexo` correspondiente.

---

## 6. Oraciones cantadas

`OracionCantada.php` (modelo) + `OracionesCantadasController.php` / `CalendarioController.php` (consumidores).

### 6.1 Modelo de calendario

- `periodicidad` (string): `"Diaria"` o `"Mensual"` (validado en `OracionCantadaRequest`).
- `dia` (int): día del mes — aplica si `Mensual`.
- `dias_semana` (array): días de la semana (`lunes`…`domingo`) — aplica si `Diaria`. Una oración puede tener varios días.
- `hora` (time): hora base / por defecto.
- `horarios_por_dia` (JSON): mapa `{ "lunes": "08:00", "miercoles": "19:00" }` con un horario propio por cada día de la semana. Solo aplica si `Diaria`. Si un día seleccionado **no** tiene hora propia, se usa `hora` como valor por defecto (retrocompatible con oraciones cargadas antes de este campo).
- `configuracion_por_mes` (JSON): array de objetos `{mes: N, periodicidad, dia, dias_semana, hora, horarios_por_dia}` que **sobrescribe** la configuración base para ese mes específico.

### 6.2 Resolución para un mes dado

Método `configuracionParaMes(CarbonInterface $month)`:

1. Toma la configuración base (incluido `horarios_por_dia`).
2. Si `configuracion_por_mes` contiene una entrada con `mes = $month->month` → fusiona campo por campo, los valores del mes específico tienen prioridad.
3. Devuelve la configuración efectiva.

Para resolver la hora de un día concreto se usa el helper `horaParaDia(array $config, string $weekday)`: devuelve `config['horarios_por_dia'][$weekday]` si existe, o `config['hora']` como fallback. Lo usan tanto el calendario (`CalendarioController::buildOracionesCantadasCalendarItems`) como la página pública (`OracionesCantadasController::sesionesDeOracion`) al generar cada ocurrencia del mes.

> Útil cuando, p. ej., una oración Diaria ocurre Lunes a las 08:00 y Miércoles a las 19:00, o cuando cambia su día/hora durante un retiro de un mes específico.

### 6.3 Regla: una configuración por mes

No se permiten **dos bloques** de `configuracion_por_mes` para el mismo mes (regla `distinct` en `OracionCantadaRequest` + `->unique('mes')` en el controller). Para tener horarios distintos por día dentro de un mes **no** se agregan varios bloques: se usa el mapa `horarios_por_dia` de ese único bloque (o de la config general).

> Normalización (`OracionesCantadasController::normalizePayload`): al guardar, `horarios_por_dia` conserva solo las claves de días realmente seleccionados (`dias_semana`) y con formato `HH:mm` válido; queda `null` si la periodicidad es `Mensual` o si no quedó ningún horario. Los inputs de hora vacíos se descartan antes de validar (`prepareForValidation`).

---

## 7. Emails y comunicación

### 7.1 Email de confirmación de inscripción

- Mail class `InscripcionConfirmada` + plantilla Blade en `resources/views/emails/`.
- Adjunta QR firmado.
- Si la inscripción tiene invitados (ver 2.6), lista cada uno con sus servicios y monto (mismo bloque
  en `inscripcion_confirmada` e `inscripcion_registrada`). Sin email propio para el invitado.
- Disparable manualmente vía comando `EnviarEmailsInscripciones` (opciones `--inscripcion_id`, `--user_id`, `--actividad_id`).

### 7.2 Reporte semanal

Comando `EnviarReporteSemanalInscripcionesActividad`:

- Construye reporte con `ReporteInscripcionesPorActividadService`.
- Destinatario:
  - Default: `email1`/`email2` de la **entidad principal** del sistema.
  - Override: `ConfiguracionSistema.envio_mail_semanal_inscripciones_destinatario` si está seteado.
- Día y hora configurables vía `ConfiguracionSistema` desde el menú **Configuración** del frontend.
- Registra cada envío en `EnvioMail` con tipo = `Automático`.

### 7.3 Plantillas de email

- Tabla `email_plantillas` almacena plantillas editables (Subject + body Blade).
- Pueden previsualizarse en `/email-preview/{tipo}/{id}` — **estas rutas están públicas** (riesgo IDOR, ver [ARCHITECTURE.md — Riesgos](ARCHITECTURE.md#9-riesgos-técnicos)).

---

## 8. Roles y permisos

Definidos en `RoleSeeder` (líneas ~17-398).

### 8.1 Roles del sistema

- **admin** — todos los permisos.
- **editor** — todo excepto: `create/update/delete roles`, `delete usuarios`, `delete tickets`.
- **asistant** — sólo: `read actividades`, CRUD de tickets (sin delete), `read novedades`.

### 8.2 Granularidad de permisos

Por cada entidad: `create X`, `read X`, `update X`, `delete X` (y algunos extra como `assign tickets`).

Entidades con permisos definidos: Entidades, Disponibilidades, Maestros, Coordinadores, Monedas, EsquemaPrecios, AplicaDescuento, Membresias, TiposActividad, EsquemaDescuentos, MetodosPago, Actividades, Clases, Ciclos, Comidas, Hospedajes, LugaresHospedaje, Modalidades, Tickets, Transportes, Streams, Novedades, Versiones, Usuarios, Grabaciones, EstadoCuentaMembresias, Permisos.

### 8.3 Chequeo en frontend

```vue
v-if="$page.props.user.permissions.includes('update actividades')"
```

### 8.4 Chequeo en backend

Algunos controllers usan `$this->authorize('...')`, otros no. **Hay endpoints sensibles (`MembresiasGestionController::asignar/eliminar`) sin chequeo explícito** — pueden estar protegidos sólo por middleware de ruta. Auditar antes de deploys a producción.

---

## 9. Configuración del sistema

Tabla `configuraciones_sistema`, modelo `ConfiguracionSistema`. Editable desde menú **Configuración**.

Parámetros conocidos:
- `mostrar_logo_entidad_principal_en_nav` (bool).
- `mostrar_logo_entidad_principal_en_footer` (bool).
- `envio_mail_semanal_inscripciones_activo` (bool).
- `envio_mail_semanal_inscripciones_dia` (día de la semana, 0-6).
- `envio_mail_semanal_inscripciones_hora` (time).
- `envio_mail_semanal_inscripciones_destinatario` (email).

---

## 10. Reglas implícitas / suposiciones

Lista de comportamientos observados que no son reglas escritas pero **impactan operativamente**:

- **Una sola entidad principal**: el código asume que existe exactamente una entidad marcada como principal (afecta logos en nav/footer y destinatario del reporte semanal).
- **Email único para usuarios y guest users**: si un email pertenece a un `User`, no se crea un `GuestUser` aunque la persona se inscriba como invitado.
- **Stock nunca negativo**: la lógica descuenta inventario sólo si hay disponibilidad. No hay overbooking explícito.
- **Sin reservas con expiración**: el inventario se descuenta inmediatamente al confirmar la inscripción; no hay un estado "reservado pero no confirmado".
- **El QR de asistencia no expira**: validez ilimitada hasta cambio de `APP_KEY`. Considerar política de rotación si se considera dato sensible.

---

## 11. Glosario

| Término | Significado |
|---|---|
| Tharpa | Literatura budista sagrada (libros) |
| Entidad | Sede/centro físico del movimiento |
| Ciclo | Período de tiempo agrupador de clases |
| Tarjeta Kadampa | Membresía del centro |
| Oración cantada | Práctica espiritual con calendario propio |
| Botón de pago | URL hacia procesador externo de pagos |
| Esquema de precio | Tabla de precios por membresía × moneda |
| Esquema de descuento | Tabla de precios reducidos para pago anticipado |
| Guest user | Persona inscripta sin cuenta registrada en el sistema (es titular de una inscripción) |
| Invitado / acompañante | Familiar o acompañante que la persona principal suma a **su** inscripción (tabla `invitados`). No es usuario ni titular; paga precio general. Distinto de *Guest user* |
| Lugar de hospedaje | Lugar físico que aloja (`LugarHospedaje`): hotel, monasterio, etc. |
| Acomodación | Tipo de habitación dentro de un lugar (`Hospedaje`: nombre + precio). Es lo que elige el inscripto |
| Cupo de acomodación | Cantidad de unidades de una acomodación **por actividad** (`actividad_hospedaje.cantidad`; null = ilimitado). Se reserva al inscribir y se libera al borrar/editar |
